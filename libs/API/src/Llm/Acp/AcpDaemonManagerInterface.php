<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * Interface for managing the ACP daemon and its agent sessions.
 *
 * The daemon is a persistent background process that manages multiple
 * ACP agent subprocesses, one per session (browser tab/user).
 */
interface AcpDaemonManagerInterface
{
    /**
     * Start the daemon process (without any agent — starts "empty").
     *
     * @throws RuntimeException If the daemon fails to start
     */
    public function start(): void;

    /**
     * Stop the daemon process (terminates all agent sessions).
     */
    public function stop(): void;

    /**
     * Check if the daemon process is running and responsive.
     */
    public function isRunning(): bool;

    /**
     * Start an agent session within the running daemon.
     *
     * @param list<string> $args CLI arguments for the agent
     * @param array<string, string> $env Environment variables for the agent
     * @return array{agentName: string, agentVersion: string}
     *
     * @throws RuntimeException If the session fails to start
     */
    public function startSession(string $sessionId, string $command, array $args = [], array $env = []): array;

    /**
     * Stop a specific agent session.
     */
    public function stopSession(string $sessionId): void;

    /**
     * Check if a session's agent is active and responsive.
     */
    public function isSessionActive(string $sessionId): bool;

    /**
     * Send a prompt to a specific session's agent.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     *
     * @throws RuntimeException If the session is not active or communication fails
     */
    public function sendPrompt(string $sessionId, array $messages, string $customPrompt, float $timeout): array;

    public function getSocketPath(): string;

    public function getPidFilePath(): string;

    public function getLogFilePath(): string;
}
