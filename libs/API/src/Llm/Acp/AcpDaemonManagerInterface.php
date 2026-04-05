<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * Interface for managing the ACP daemon lifecycle.
 */
interface AcpDaemonManagerInterface
{
    /**
     * Start the ACP daemon as a background process.
     *
     * @param list<string> $args CLI arguments for the agent
     * @param array<string, string> $env Environment variables for the agent
     *
     * @throws RuntimeException If the daemon fails to start
     */
    public function start(string $command, array $args = [], array $env = []): void;

    /**
     * Stop the running ACP daemon.
     */
    public function stop(): void;

    /**
     * Check if the ACP daemon is running and responsive.
     */
    public function isRunning(): bool;

    /**
     * Send a prompt to the running daemon and return the response.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     *
     * @throws RuntimeException If the daemon is not running or communication fails
     */
    public function sendPrompt(array $messages, string $customPrompt, float $timeout): array;

    public function getSocketPath(): string;

    public function getPidFilePath(): string;

    public function getLogFilePath(): string;
}
