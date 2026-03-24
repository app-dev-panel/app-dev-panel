<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

interface LlmSettingsInterface
{
    public function getApiKey(): ?string;

    public function setApiKey(?string $apiKey): void;

    public function getProvider(): string;

    public function setProvider(string $provider): void;

    public function getModel(): ?string;

    public function setModel(?string $model): void;

    public function isConnected(): bool;

    public function clear(): void;

    /**
     * @return array{connected: bool, provider: string, model: string|null}
     */
    public function toArray(): array;
}
