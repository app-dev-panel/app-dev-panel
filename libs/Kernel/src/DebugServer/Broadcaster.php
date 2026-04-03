<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Throwable;

/**
 * Broadcasts messages to all connected debug servers.
 *
 * Cross-platform:
 * - Linux/macOS: discovers Unix domain sockets via .sock files
 * - Windows: discovers UDP ports via .port files
 */
final class Broadcaster
{
    /**
     * Broadcasts a message to all connected debug servers.
     *
     * @return array Unique errors encountered during broadcast.
     */
    public function broadcast(int $type, string $data): array
    {
        if (Connection::isWindows()) {
            return $this->broadcastUdp($type, $data);
        }

        return $this->broadcastUnix($type, $data);
    }

    private function broadcastUnix(int $type, string $data): array
    {
        $files = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        $uniqueErrors = [];
        $payload = json_encode([$type, $data], JSON_THROW_ON_ERROR);

        foreach ($files as $file) {
            $socket = @fsockopen('udg://' . $file, -1, $errno, $errstr);

            if ($errno === SOCKET_ECONNREFUSED) {
                if (file_exists($file)) {
                    unlink($file);
                }
                continue;
            }
            if ($errno !== 0) {
                $uniqueErrors[$errno] = $errstr;
                continue;
            }
            try {
                if (!$this->fwriteStream($socket, $payload)) {
                    $uniqueErrors[] = error_get_last();
                    continue;
                }
            } catch (Throwable $e) {
                throw $e;
            } finally {
                fclose($socket);
            }
        }

        return $uniqueErrors;
    }

    private function broadcastUdp(int $type, string $data): array
    {
        $files = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        $uniqueErrors = [];
        $payload = json_encode([$type, $data], JSON_THROW_ON_ERROR);

        foreach ($files as $file) {
            $portStr = @file_get_contents($file);
            if ($portStr === false) {
                continue;
            }

            $port = (int) $portStr;
            if ($port <= 0) {
                if (file_exists($file)) {
                    unlink($file);
                }
                continue;
            }

            $socket = @fsockopen('udp://127.0.0.1', $port, $errno, $errstr);

            if ($errno !== 0) {
                $uniqueErrors[$errno] = $errstr;
                continue;
            }
            if ($socket === false) {
                continue;
            }

            try {
                if (!$this->fwriteStream($socket, $payload)) {
                    $uniqueErrors[] = error_get_last();
                    continue;
                }
            } catch (Throwable $e) {
                throw $e;
            } finally {
                fclose($socket);
            }
        }

        return $uniqueErrors;
    }

    /**
     * @param resource $fp
     */
    private function fwriteStream($fp, string $data): int|false
    {
        $data = base64_encode($data);
        $strlen = strlen($data);
        fwrite($fp, pack('P', $strlen));
        for ($written = 0; $written < $strlen; $written += $fwrite) {
            $fwrite = fwrite($fp, substr($data, $written), Connection::DEFAULT_BUFFER_SIZE);
            usleep(Connection::DEFAULT_TIMEOUT * 5);
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }
}
