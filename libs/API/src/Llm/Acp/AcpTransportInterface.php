<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Interface for ACP agent subprocess communication.
 */
interface AcpTransportInterface
{
    /**
     * Spawn an ACP agent as a subprocess.
     *
     * @param list<string> $args Additional CLI arguments
     * @param array<string, string> $env Environment variables
     */
    public function spawn(string $command, array $args = [], array $env = [], ?string $cwd = null): void;

    /**
     * Send a JSON-RPC message to the agent's stdin.
     */
    public function send(array $message): void;

    /**
     * Read a single JSON-RPC message from stdout.
     * Returns null on timeout or EOF.
     */
    public function receive(float $timeoutSeconds = 30.0): ?array;

    /**
     * Read stderr output (non-blocking).
     */
    public function readStderr(): string;

    /**
     * Check if the subprocess is still running.
     */
    public function isAlive(): bool;

    /**
     * Close the subprocess and all pipes.
     */
    public function close(): void;
}
