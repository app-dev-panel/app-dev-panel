<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

/**
 * Broadcasts messages to all connected debug servers.
 *
 * Cross-platform: discovers servers via .sock files (Unix) or .port files (Windows).
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
        $files = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        if ($files === false || $files === []) {
            return [];
        }

        $payload = json_encode([$type, $data], JSON_THROW_ON_ERROR);
        $uniqueErrors = [];

        foreach ($files as $file) {
            $socket = Connection::isWindows()
                ? $this->openUdpSocket($file, $uniqueErrors)
                : $this->openUnixSocket($file, $uniqueErrors);

            if ($socket === null) {
                continue;
            }

            try {
                $this->writePayload($socket, $payload, $uniqueErrors);
            } finally {
                fclose($socket);
            }
        }

        return $uniqueErrors;
    }

    /**
     * @return resource|null
     */
    private function openUnixSocket(string $file, array &$errors)
    {
        $socket = @fsockopen('udg://' . $file, -1, $errno, $errstr);

        if ($errno === SOCKET_ECONNREFUSED) {
            @unlink($file);
            return null;
        }
        if ($socket === false || $errno !== 0) {
            if ($errno !== 0) {
                $errors[$errno] = $errstr;
            }
            return null;
        }

        return $socket;
    }

    /**
     * @return resource|null
     */
    private function openUdpSocket(string $file, array &$errors)
    {
        $portStr = @file_get_contents($file);
        if ($portStr === false) {
            return null;
        }

        $port = (int) $portStr;
        if ($port <= 0) {
            @unlink($file);
            return null;
        }

        $socket = @fsockopen('udp://127.0.0.1', $port, $errno, $errstr);

        if ($socket === false || $errno !== 0) {
            if ($errno !== 0) {
                $errors[$errno] = $errstr;
            }
            @unlink($file);
            return null;
        }

        return $socket;
    }

    /**
     * Writes an entire message as one atomic datagram (correct for SOCK_DGRAM).
     * Format: 8-byte length header + base64-encoded payload.
     *
     * @param resource $fp
     */
    private function writePayload($fp, string $payload, array &$errors): void
    {
        stream_set_write_buffer($fp, 0);

        $encoded = base64_encode($payload);
        $datagram = pack('P', strlen($encoded)) . $encoded;

        if (fwrite($fp, $datagram) === false) {
            $errors[] = error_get_last();
        }
    }
}
