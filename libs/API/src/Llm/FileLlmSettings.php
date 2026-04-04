<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

final class FileLlmSettings implements LlmSettingsInterface
{
    private const int DEFAULT_TIMEOUT = 30;
    private const string DEFAULT_CUSTOM_PROMPT = 'Reply in English. Be concise and actionable — focus on root causes and fixes, not descriptions of what the code does.';
    private const string DEFAULT_ACP_COMMAND = 'claude';

    private ?string $apiKey = null;
    private string $provider = 'openrouter';
    private ?string $model = null;
    private int $timeout = self::DEFAULT_TIMEOUT;
    private string $customPrompt = self::DEFAULT_CUSTOM_PROMPT;
    private string $acpCommand = self::DEFAULT_ACP_COMMAND;
    /** @var list<string> */
    private array $acpArgs = [];
    /** @var array<string, string> */
    private array $acpEnv = [];
    private bool $loaded = false;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function getApiKey(): ?string
    {
        $this->load();

        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->load();
        $this->apiKey = $apiKey;
        $this->save();
    }

    public function getProvider(): string
    {
        $this->load();

        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->load();
        $this->provider = $provider;
        $this->save();
    }

    public function getModel(): ?string
    {
        $this->load();

        return $this->model;
    }

    public function setModel(?string $model): void
    {
        $this->load();
        $this->model = $model;
        $this->save();
    }

    public function getTimeout(): int
    {
        $this->load();

        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->load();
        $this->timeout = max(5, min(300, $timeout));
        $this->save();
    }

    public function getCustomPrompt(): string
    {
        $this->load();

        return $this->customPrompt;
    }

    public function setCustomPrompt(string $prompt): void
    {
        $this->load();
        $this->customPrompt = $prompt;
        $this->save();
    }

    public function getAcpCommand(): string
    {
        $this->load();

        return $this->acpCommand;
    }

    public function setAcpCommand(string $command): void
    {
        $this->load();
        $this->acpCommand = $command;
        $this->save();
    }

    public function getAcpArgs(): array
    {
        $this->load();

        return $this->acpArgs;
    }

    public function setAcpArgs(array $args): void
    {
        $this->load();
        $this->acpArgs = array_values($args);
        $this->save();
    }

    public function getAcpEnv(): array
    {
        $this->load();

        return $this->acpEnv;
    }

    public function setAcpEnv(array $env): void
    {
        $this->load();
        $this->acpEnv = $env;
        $this->save();
    }

    public function isConnected(): bool
    {
        $this->load();

        if ($this->provider === 'acp') {
            return $this->acpCommand !== '';
        }

        return $this->apiKey !== null && $this->apiKey !== '';
    }

    public function clear(): void
    {
        $this->apiKey = null;
        $this->model = null;
        $this->provider = 'openrouter';
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->customPrompt = self::DEFAULT_CUSTOM_PROMPT;
        $this->acpCommand = self::DEFAULT_ACP_COMMAND;
        $this->acpArgs = [];
        $this->acpEnv = [];
        $this->loaded = true;

        $file = $this->filePath();
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @return array{connected: bool, provider: string, model: string|null, timeout: int, customPrompt: string}
     */
    public function toArray(): array
    {
        $this->load();

        return [
            'connected' => $this->isConnected(),
            'provider' => $this->provider,
            'model' => $this->model,
            'timeout' => $this->timeout,
            'customPrompt' => $this->customPrompt,
            'acpCommand' => $this->acpCommand,
            'acpArgs' => $this->acpArgs,
            'acpEnv' => $this->acpEnv,
        ];
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $file = $this->filePath();
        if (!file_exists($file)) {
            return;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return;
        }

        /** @var array{apiKey?: string, provider?: string, model?: string, timeout?: int, customPrompt?: string, acpCommand?: string, acpArgs?: list<string>, acpEnv?: array<string, string>} $data */
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->apiKey = isset($data['apiKey']) && is_string($data['apiKey']) ? $data['apiKey'] : null;
        $this->provider = isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : 'openrouter';
        $this->model = isset($data['model']) && is_string($data['model']) ? $data['model'] : null;
        $this->timeout = isset($data['timeout']) && is_int($data['timeout']) ? $data['timeout'] : self::DEFAULT_TIMEOUT;
        $this->customPrompt = isset($data['customPrompt']) && is_string($data['customPrompt'])
            ? $data['customPrompt']
            : self::DEFAULT_CUSTOM_PROMPT;
        $this->acpCommand = isset($data['acpCommand']) && is_string($data['acpCommand'])
            ? $data['acpCommand']
            : self::DEFAULT_ACP_COMMAND;
        $this->acpArgs = isset($data['acpArgs']) && is_array($data['acpArgs']) ? $data['acpArgs'] : [];
        $this->acpEnv = isset($data['acpEnv']) && is_array($data['acpEnv']) ? $data['acpEnv'] : [];
    }

    private function save(): void
    {
        $dir = dirname($this->filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents(
            $this->filePath(),
            json_encode([
                'apiKey' => $this->apiKey,
                'provider' => $this->provider,
                'model' => $this->model,
                'timeout' => $this->timeout,
                'customPrompt' => $this->customPrompt,
                'acpCommand' => $this->acpCommand,
                'acpArgs' => $this->acpArgs,
                'acpEnv' => $this->acpEnv,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        );
    }

    private function filePath(): string
    {
        return $this->storagePath . '/.llm-settings.json';
    }
}
