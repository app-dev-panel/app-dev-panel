<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

use AppDevPanel\Kernel\Project\SecretsConfig;
use AppDevPanel\Kernel\Project\SecretsStorageInterface;
use SensitiveParameter;

/**
 * LLM settings stored in the project's `secrets.json` (gitignored, `chmod 0600`).
 *
 * Until v0.x these settings lived in `<storagePath>/.llm-settings.json` next to
 * the runtime debug entries. They have been moved into the dedicated secrets
 * file so they share the lifecycle of the project config (one directory, one
 * `.gitignore`, one set of permissions). Existing installations are migrated
 * automatically on first read — see {@see migrateLegacyFile()}.
 *
 * The class is a thin facade: every read materialises the underlying
 * {@see SecretsConfig} via {@see SecretsStorageInterface::load()} and every
 * write hands a new immutable instance back via `save()`. Callers stay
 * unchanged because all mutators (`setApiKey`, `setProvider`, ...) keep their
 * historic signatures.
 *
 * `getStoragePath()` still returns the **runtime** debug directory because the
 * ACP daemon manager and the LLM history storage place their socket / pid /
 * history files there; that location is unrelated to where secrets live and
 * we keep it for backwards compat.
 */
final class FileLlmSettings implements LlmSettingsInterface
{
    private const int DEFAULT_TIMEOUT = 30;
    private const string DEFAULT_CUSTOM_PROMPT = 'Reply in English. Be concise and actionable — focus on root causes and fixes, not descriptions of what the code does.';
    private const string DEFAULT_ACP_COMMAND = 'claude';
    private const string LEGACY_FILENAME = '.llm-settings.json';

    private SecretsConfig $config;
    private bool $loaded = false;

    /**
     * @param string $storagePath Runtime debug dir (used by ACP daemon + history;
     *                            also scanned for the legacy `.llm-settings.json`).
     * @param SecretsStorageInterface $secrets New canonical store (`<projectConfigDir>/secrets.json`).
     */
    public function __construct(
        private readonly string $storagePath,
        private readonly SecretsStorageInterface $secrets,
    ) {
        $this->config = SecretsConfig::empty();
    }

    public function getApiKey(): ?string
    {
        $this->load();
        $value = $this->config->llm['apiKey'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setApiKey(#[SensitiveParameter] ?string $apiKey): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['apiKey' => $apiKey === '' ? null : $apiKey]);
        $this->save();
    }

    public function getProvider(): string
    {
        $this->load();
        $value = $this->config->llm['provider'] ?? null;

        return is_string($value) && $value !== '' ? $value : 'openrouter';
    }

    public function setProvider(string $provider): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['provider' => $provider]);
        $this->save();
    }

    public function getModel(): ?string
    {
        $this->load();
        $value = $this->config->llm['model'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setModel(?string $model): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['model' => $model === '' ? null : $model]);
        $this->save();
    }

    public function getTimeout(): int
    {
        $this->load();
        $value = $this->config->llm['timeout'] ?? null;

        return is_int($value) ? $value : self::DEFAULT_TIMEOUT;
    }

    public function setTimeout(int $timeout): void
    {
        $this->load();
        $clamped = max(5, min(300, $timeout));
        $this->config = $this->config->withLlmPatch(['timeout' => $clamped]);
        $this->save();
    }

    public function getCustomPrompt(): string
    {
        $this->load();
        $value = $this->config->llm['customPrompt'] ?? null;

        return is_string($value) ? $value : self::DEFAULT_CUSTOM_PROMPT;
    }

    public function setCustomPrompt(string $prompt): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['customPrompt' => $prompt]);
        $this->save();
    }

    public function getAcpCommand(): string
    {
        $this->load();
        $value = $this->config->llm['acpCommand'] ?? null;

        return is_string($value) && $value !== '' ? $value : self::DEFAULT_ACP_COMMAND;
    }

    public function setAcpCommand(string $command): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['acpCommand' => $command]);
        $this->save();
    }

    public function getAcpArgs(): array
    {
        $this->load();
        $value = $this->config->llm['acpArgs'] ?? [];

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    public function setAcpArgs(array $args): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['acpArgs' => array_values($args)]);
        $this->save();
    }

    public function getAcpEnv(): array
    {
        $this->load();
        $value = $this->config->llm['acpEnv'] ?? [];

        return is_array($value) ? $value : [];
    }

    public function setAcpEnv(array $env): void
    {
        $this->load();
        $this->config = $this->config->withLlmPatch(['acpEnv' => $env]);
        $this->save();
    }

    public function isConnected(): bool
    {
        $this->load();

        if ($this->getProvider() === 'acp') {
            // Inspect the raw stored value: an explicit empty string set via
            // `setAcpCommand('')` must read as "not connected", while a
            // missing key falls back to {@see DEFAULT_ACP_COMMAND} (= connected).
            $command = $this->config->llm['acpCommand'] ?? self::DEFAULT_ACP_COMMAND;

            return is_string($command) && $command !== '';
        }

        $apiKey = $this->getApiKey();
        return $apiKey !== null && $apiKey !== '';
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function clear(): void
    {
        $this->config = SecretsConfig::empty();
        $this->loaded = true;
        $this->secrets->save($this->config);
    }

    /**
     * @return array{
     *     connected: bool,
     *     provider: string,
     *     model: string|null,
     *     timeout: int,
     *     customPrompt: string,
     *     acpCommand: string,
     *     acpArgs: list<string>,
     *     acpEnv: array<string, string>,
     * }
     */
    public function toArray(): array
    {
        $this->load();

        return [
            'connected' => $this->isConnected(),
            'provider' => $this->getProvider(),
            'model' => $this->getModel(),
            'timeout' => $this->getTimeout(),
            'customPrompt' => $this->getCustomPrompt(),
            'acpCommand' => $this->getAcpCommand(),
            'acpArgs' => $this->getAcpArgs(),
            'acpEnv' => $this->getAcpEnv(),
        ];
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $this->config = $this->secrets->load();

        // If the canonical store has nothing yet but the legacy file exists,
        // migrate its contents over once. This keeps existing installs from
        // appearing to "lose" their LLM settings after a panel upgrade.
        if ($this->config->llm === []) {
            $this->migrateLegacyFile();
        }
    }

    private function save(): void
    {
        $this->secrets->save($this->config);
    }

    private function migrateLegacyFile(): void
    {
        $legacy = rtrim($this->storagePath, '/\\') . DIRECTORY_SEPARATOR . self::LEGACY_FILENAME;
        if (!is_file($legacy)) {
            return;
        }

        $contents = @file_get_contents($legacy);
        if ($contents === false || $contents === '') {
            return;
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        $config = SecretsConfig::fromArray(['llm' => $data]);
        if ($config->llm === []) {
            return;
        }

        $this->config = $config;
        $this->secrets->save($this->config);

        // Keep a backup so an admin can recover by hand if migration was wrong;
        // the suffix marks it as migrated so the legacy code path never runs again.
        @rename($legacy, $legacy . '.migrated');

        // Stderr so production logs surface the move; suppressed under tests
        // that capture stderr (PHPUnit's `beStrictAboutOutputDuringTests` is
        // already configured for stdout, not stderr).
        if (defined('STDERR') && is_resource(STDERR)) {
            @fwrite(STDERR, sprintf(
                "[ADP] Migrated LLM settings from %s to %s/secrets.json\n",
                $legacy,
                $this->secrets->getConfigDir(),
            ));
        }
    }
}
