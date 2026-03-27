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

    public function getTimeout(): int;

    public function setTimeout(int $timeout): void;

    public function getCustomPrompt(): string;

    public function setCustomPrompt(string $prompt): void;

    public function isConnected(): bool;

    public function clear(): void;

    /**
     * @return array{connected: bool, provider: string, model: string|null, timeout: int, customPrompt: string}
     */
    public function toArray(): array;
}
