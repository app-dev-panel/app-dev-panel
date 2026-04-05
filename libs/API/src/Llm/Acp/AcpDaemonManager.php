<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * Manages the ACP daemon lifecycle: start, stop, status.
 *
 * The daemon is a persistent background PHP process that maintains
 * an ACP agent subprocess and accepts prompt requests via Unix socket.
 */
final class AcpDaemonManager implements AcpDaemonManagerInterface
{
    private const float START_TIMEOUT = 15.0;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    /**
     * Start the ACP daemon as a background process.
     *
     * @param list<string> $args CLI arguments for the agent
     * @param array<string, string> $env Environment variables for the agent
     *
     * @throws RuntimeException If the daemon fails to start
     */
    public function start(string $command, array $args = [], array $env = []): void
    {
        if ($this->isRunning()) {
            return;
        }

        $this->cleanup();

        $socketPath = $this->getSocketPath();
        $pidFile = $this->getPidFilePath();
        $daemonScript = __DIR__ . '/acp-daemon-runner.php';

        if (!file_exists($daemonScript)) {
            throw new RuntimeException('ACP daemon script not found: ' . $daemonScript);
        }

        $dir = dirname($socketPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $logFile = $this->getLogFilePath();

        $cmd = sprintf(
            '%s %s --socket=%s --pid=%s --command=%s --args=%s --env=%s > /dev/null 2>%s &',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($daemonScript),
            escapeshellarg($socketPath),
            escapeshellarg($pidFile),
            escapeshellarg($command),
            escapeshellarg(json_encode($args, JSON_THROW_ON_ERROR)),
            escapeshellarg(json_encode($env, JSON_THROW_ON_ERROR)),
            escapeshellarg($logFile),
        );

        exec($cmd);

        $this->waitForSocket(self::START_TIMEOUT);
    }

    /**
     * Stop the running ACP daemon.
     */
    public function stop(): void
    {
        $socketPath = $this->getSocketPath();

        // Try graceful shutdown via socket first
        if (file_exists($socketPath)) {
            try {
                $socket = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 3.0);
                if ($socket !== false) {
                    fwrite($socket, json_encode(['action' => 'shutdown']) . "\n");
                    // Wait briefly for acknowledgment
                    stream_set_timeout($socket, 2);
                    fgets($socket);
                    fclose($socket);

                    // Give daemon time to clean up
                    usleep(500_000);
                }
            } catch (\Throwable) {
                // Ignore — fall through to PID-based kill
            }
        }

        // Force-kill via PID if still running
        $pidFile = $this->getPidFilePath();
        if (file_exists($pidFile)) {
            $pid = (int) file_get_contents($pidFile);
            if ($pid > 0 && $this->isProcessAlive($pid)) {
                posix_kill($pid, SIGTERM);
                usleep(500_000);

                if ($this->isProcessAlive($pid)) {
                    posix_kill($pid, SIGKILL);
                }
            }
        }

        $this->cleanup();
    }

    /**
     * Check if the ACP daemon is running and responsive.
     */
    public function isRunning(): bool
    {
        $socketPath = $this->getSocketPath();

        if (!file_exists($socketPath)) {
            return false;
        }

        // Verify via ping
        try {
            $socket = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 2.0);
            if ($socket === false) {
                return false;
            }

            fwrite($socket, json_encode(['action' => 'ping']) . "\n");
            stream_set_timeout($socket, 2);
            $response = fgets($socket);
            fclose($socket);

            if ($response === false) {
                return false;
            }

            $data = json_decode(trim($response), true);

            return is_array($data) && ($data['ok'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a prompt to the running daemon and return the response.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array<string, mixed>
     *
     * @throws RuntimeException If the daemon is not running or communication fails
     */
    public function sendPrompt(array $messages, string $customPrompt, float $timeout): array
    {
        $socketPath = $this->getSocketPath();

        $socket = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 5.0);

        if ($socket === false) {
            throw new RuntimeException("Cannot connect to ACP daemon: {$errstr}");
        }

        $request = json_encode(
            [
                'action' => 'prompt',
                'messages' => $messages,
                'customPrompt' => $customPrompt,
                'timeout' => $timeout,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        fwrite($socket, $request . "\n");

        // Read response with generous timeout (agent may take a while)
        stream_set_timeout($socket, (int) $timeout + 10);
        $responseLine = fgets($socket);
        fclose($socket);

        if ($responseLine === false) {
            throw new RuntimeException('ACP daemon did not respond (timeout or connection closed).');
        }

        $data = json_decode(trim($responseLine), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid response from ACP daemon.');
        }

        return $data;
    }

    public function getSocketPath(): string
    {
        // Unix sockets have a 103-byte path limit. Use /tmp with a hash to keep it short.
        $hash = substr(md5($this->storagePath), 0, 12);

        return sys_get_temp_dir() . "/adp-acp-{$hash}.sock";
    }

    public function getPidFilePath(): string
    {
        return $this->storagePath . '/.acp-daemon.pid';
    }

    public function getLogFilePath(): string
    {
        return $this->storagePath . '/.acp-daemon.log';
    }

    /**
     * Wait for the daemon socket to become available.
     */
    private function waitForSocket(float $timeout): void
    {
        $deadline = microtime(true) + $timeout;
        $socketPath = $this->getSocketPath();

        while (microtime(true) < $deadline) {
            if (file_exists($socketPath)) {
                // Verify it's responsive
                if ($this->isRunning()) {
                    return;
                }
            }

            usleep(200_000); // 200ms
        }

        $logTail = $this->readLogTail();
        $pidFile = $this->getPidFilePath();

        if (!file_exists($pidFile)) {
            throw new RuntimeException('ACP daemon failed to start (no PID file created).' . $logTail);
        }

        throw new RuntimeException('ACP daemon started but socket is not responding.' . $logTail);
    }

    private function cleanup(): void
    {
        $socketPath = $this->getSocketPath();
        $pidFile = $this->getPidFilePath();

        if (file_exists($socketPath)) {
            @unlink($socketPath);
        }
        if (file_exists($pidFile)) {
            @unlink($pidFile);
        }
    }

    private function isProcessAlive(int $pid): bool
    {
        if (!function_exists('posix_kill')) {
            return file_exists("/proc/{$pid}/status");
        }

        return posix_kill($pid, 0);
    }

    private function readLogTail(int $maxBytes = 2000): string
    {
        $logFile = $this->getLogFilePath();
        if (!file_exists($logFile)) {
            return '';
        }

        $content = file_get_contents($logFile);
        if ($content === false || $content === '') {
            return '';
        }

        if (strlen($content) > $maxBytes) {
            $content = '...' . substr($content, -$maxBytes);
        }

        return "\nDaemon log:\n" . trim($content);
    }
}
