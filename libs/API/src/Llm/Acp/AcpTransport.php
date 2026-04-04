<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * Manages an ACP agent subprocess and provides JSON-RPC communication over stdio pipes.
 *
 * Spawns the agent via proc_open, writes JSON-RPC messages to stdin,
 * reads responses from stdout. Messages are newline-delimited JSON.
 */
final class AcpTransport implements AcpTransportInterface
{
    /** @var resource|null */
    private mixed $process = null;

    /** @var resource|null stdin pipe */
    private mixed $stdin = null;

    /** @var resource|null stdout pipe */
    private mixed $stdout = null;

    /** @var resource|null stderr pipe */
    private mixed $stderr = null;

    /**
     * Spawn an ACP agent as a subprocess.
     *
     * @param list<string> $args Additional CLI arguments
     * @param array<string, string> $env Environment variables (merged with current env)
     */
    public function spawn(string $command, array $args = [], array $env = [], ?string $cwd = null): void
    {
        if ($this->process !== null) {
            throw new RuntimeException('ACP transport already has a running process.');
        }

        $fullCommand = array_merge([$command], $args);
        $commandLine = implode(' ', array_map('escapeshellarg', $fullCommand));

        $descriptors = [
            0 => ['pipe', 'r'], // stdin — we write, child reads
            1 => ['pipe', 'w'], // stdout — child writes, we read
            2 => ['pipe', 'w'], // stderr — child writes, we read
        ];

        $mergedEnv = array_merge(getenv() ?: [], $env);

        $process = proc_open($commandLine, $descriptors, $pipes, $cwd, $mergedEnv);

        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Failed to spawn ACP agent: %s', $commandLine));
        }

        $this->process = $process;
        $this->stdin = $pipes[0] ?? null;
        $this->stdout = $pipes[1] ?? null;
        $this->stderr = $pipes[2] ?? null;

        if ($this->stdin === null || $this->stdout === null || $this->stderr === null) {
            proc_close($process);
            $this->process = null;
            throw new RuntimeException('Failed to open stdio pipes for ACP agent.');
        }

        // Keep stderr non-blocking for diagnostic reads.
        stream_set_blocking($this->stderr, false);
    }

    /**
     * Send a JSON-RPC message to the agent's stdin.
     */
    public function send(array $message): void
    {
        if ($this->stdin === null) {
            throw new RuntimeException('ACP transport is not connected.');
        }

        $json = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $written = fwrite($this->stdin, $json . "\n");

        if ($written === false) {
            throw new RuntimeException('Failed to write to ACP agent stdin.');
        }

        fflush($this->stdin);
    }

    /**
     * Read a single JSON-RPC message from stdout.
     *
     * Blocks up to $timeoutSeconds waiting for a complete line.
     * Returns null on timeout or EOF.
     */
    public function receive(float $timeoutSeconds = 30.0): ?array
    {
        if ($this->stdout === null) {
            throw new RuntimeException('ACP transport is not connected.');
        }

        $deadline = microtime(true) + $timeoutSeconds;

        while (microtime(true) < $deadline) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                break;
            }

            // Use stream_select to wait for data with timeout.
            $read = [$this->stdout];
            $write = null;
            $except = null;
            $seconds = (int) $remaining;
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);

            $ready = @stream_select($read, $write, $except, $seconds, $microseconds);

            if ($ready === false || $ready === 0) {
                // Timeout or error — check if process is still alive.
                if (!$this->isAlive()) {
                    return null;
                }
                continue;
            }

            $line = fgets($this->stdout);

            if ($line === false) {
                if (!$this->isAlive()) {
                    return null;
                }
                continue;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                throw new RuntimeException('Invalid JSON-RPC message from ACP agent: expected object.');
            }

            return $decoded;
        }

        return null;
    }

    /**
     * Read stderr output (non-blocking). Useful for diagnostics.
     */
    public function readStderr(): string
    {
        if ($this->stderr === null) {
            return '';
        }

        $output = '';
        while (($chunk = fread($this->stderr, 8192)) !== false && $chunk !== '') {
            $output .= $chunk;
        }

        return $output;
    }

    /**
     * Check if the subprocess is still running.
     */
    public function isAlive(): bool
    {
        if ($this->process === null) {
            return false;
        }

        $status = proc_get_status($this->process);

        return $status['running'] === true;
    }

    /**
     * Close the subprocess and all pipes.
     */
    public function close(): void
    {
        if ($this->stdin !== null) {
            fclose($this->stdin);
            $this->stdin = null;
        }
        if ($this->stdout !== null) {
            fclose($this->stdout);
            $this->stdout = null;
        }
        if ($this->stderr !== null) {
            fclose($this->stderr);
            $this->stderr = null;
        }
        if ($this->process !== null) {
            // Terminate the process first to avoid blocking on proc_close.
            if ($this->isAlive()) {
                proc_terminate($this->process);
            }
            proc_close($this->process);
            $this->process = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
