<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

final class FileLlmSettings implements LlmSettingsInterface
{
    private ?string $apiKey = null;
    private string $provider = 'openrouter';
    private ?string $model = null;
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

    public function isConnected(): bool
    {
        $this->load();

        return $this->apiKey !== null && $this->apiKey !== '';
    }

    public function clear(): void
    {
        $this->apiKey = null;
        $this->model = null;
        $this->provider = 'openrouter';
        $this->loaded = true;

        $file = $this->filePath();
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @return array{connected: bool, provider: string, model: string|null}
     */
    public function toArray(): array
    {
        $this->load();

        return [
            'connected' => $this->isConnected(),
            'provider' => $this->provider,
            'model' => $this->model,
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

        /** @var array{apiKey?: string, provider?: string, model?: string} $data */
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->apiKey = isset($data['apiKey']) && is_string($data['apiKey']) ? $data['apiKey'] : null;
        $this->provider = isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : 'openrouter';
        $this->model = isset($data['model']) && is_string($data['model']) ? $data['model'] : null;
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
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        );
    }

    private function filePath(): string
    {
        return $this->storagePath . '/.llm-settings.json';
    }
}
