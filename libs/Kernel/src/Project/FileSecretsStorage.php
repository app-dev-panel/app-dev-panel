<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

use AppDevPanel\Kernel\Helper\Silencer;
use JsonException;
use RuntimeException;

/**
 * JSON-file-backed implementation of {@see SecretsStorageInterface}.
 *
 * Lives at `<configDir>/secrets.json` — same directory as `project.json`.
 * Distinguishing properties from {@see FileProjectConfigStorage}:
 *
 *   - File mode `0600` (owner read/write only) — secrets are local-only.
 *   - {@see FileProjectConfigStorage} auto-creates a sibling `.gitignore`
 *     listing `secrets.json`, so this file never travels via VCS without
 *     explicit user override.
 *   - Atomic save (temp + rename) plus `LOCK_EX` against concurrent writers.
 *   - On unreadable / malformed files we return an empty config rather than
 *     throwing — same forgiving load semantics as project storage.
 */
final class FileSecretsStorage implements SecretsStorageInterface
{
    public const string SECRETS_FILENAME = 'secrets.json';

    public function __construct(
        private readonly string $configDir,
    ) {}

    public function load(): SecretsConfig
    {
        $file = $this->filePath();
        if (!is_file($file) || !is_readable($file)) {
            return SecretsConfig::empty();
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return SecretsConfig::empty();
        }

        // An empty file is a JsonException below and therefore also yields the empty config.

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return SecretsConfig::empty();
        }

        return SecretsConfig::fromArray($data);
    }

    public function save(SecretsConfig $config): void
    {
        $this->ensureConfigDir();

        $payload =
            json_encode(
                $config->toArray(),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) . "\n";

        $target = $this->filePath();
        $tmp = $target . '.tmp';

        if (file_put_contents($tmp, $payload, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Failed to write secrets to "%s".', $tmp));
        }

        chmod($tmp, 0o600);

        // Muted: the rename races with concurrent writers / external editors; failure is reported below.
        if (!Silencer::run(static fn(): bool => rename($tmp, $target))) {
            unlink($tmp); // a failed rename leaves the temp file in place
            throw new RuntimeException(sprintf('Failed to move secrets to "%s".', $target));
        }
    }

    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    private function filePath(): string
    {
        return rtrim($this->configDir, '/\\') . DIRECTORY_SEPARATOR . self::SECRETS_FILENAME;
    }

    private function ensureConfigDir(): void
    {
        if (is_dir($this->configDir)) {
            return;
        }

        // Muted: a concurrent process may create the directory between the checks.
        $created = Silencer::run(fn(): bool => mkdir($this->configDir, 0o755, true));
        if (!$created && !is_dir($this->configDir)) {
            throw new RuntimeException(sprintf('Failed to create config directory "%s".', $this->configDir));
        }
    }
}
