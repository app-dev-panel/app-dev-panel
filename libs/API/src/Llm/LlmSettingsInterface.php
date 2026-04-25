<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

use SensitiveParameter;

interface LlmSettingsInterface
{
    public function getApiKey(): ?string;

    public function setApiKey(#[SensitiveParameter] ?string $apiKey): void;

    public function getProvider(): string;

    public function setProvider(string $provider): void;

    public function getModel(): ?string;

    public function setModel(?string $model): void;

    public function getTimeout(): int;

    public function setTimeout(int $timeout): void;

    public function getCustomPrompt(): string;

    public function setCustomPrompt(string $prompt): void;

    public function getAcpCommand(): string;

    public function setAcpCommand(string $command): void;

    /**
     * @return list<string>
     */
    public function getAcpArgs(): array;

    /**
     * @param list<string> $args
     */
    public function setAcpArgs(array $args): void;

    /**
     * @return array<string, string>
     */
    public function getAcpEnv(): array;

    /**
     * @param array<string, string> $env
     */
    public function setAcpEnv(array $env): void;

    public function isConnected(): bool;

    public function clear(): void;

    /**
     * Storage directory path (used for daemon socket/pid files).
     */
    public function getStoragePath(): string;

    /**
     * @return array{connected: bool, provider: string, model: string|null, timeout: int, customPrompt: string, acpCommand: string, acpArgs: list<string>, acpEnv: array<string, string>}
     */
    public function toArray(): array;
}
