<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

/**
 * Broadcasts messages to all connected debug servers.
 *
 * Cross-platform: discovers servers via .sock files (Unix) or .port files (Windows).
 *
 * Every socket operation is bounded by {@see self::DEFAULT_TIMEOUT} seconds
 * (see {@see DatagramSocket}): a receiver whose buffer is full costs at most one
 * timeout per discovery file instead of the process-wide `default_socket_timeout`,
 * and PHP notices raised by the stream layer are returned as errors, never printed.
 */
final class Broadcaster implements BroadcasterInterface
{
    /**
     * Hard cap on the connect / send wait, in seconds.
     */
    public const float DEFAULT_TIMEOUT = 0.2;

    private readonly float $timeout;

    public function __construct(float $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->timeout = max(0.001, min($timeout, self::DEFAULT_TIMEOUT));
    }

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

        // Format: 8-byte length header + base64-encoded JSON payload, one datagram per message.
        $encoded = base64_encode(json_encode([$type, $data], JSON_THROW_ON_ERROR));
        $datagram = pack('P', strlen($encoded)) . $encoded;
        $uniqueErrors = [];

        foreach ($files as $file) {
            $socket = $this->open($file, $uniqueErrors);
            if ($socket === null) {
                continue;
            }

            $error = $socket->send($datagram);
            $socket->close();
            if ($error !== null) {
                $uniqueErrors[$error] = $error;
            }
        }

        return $uniqueErrors;
    }

    /**
     * Connects to the server behind a discovery file; removes the file when it is stale.
     */
    private function open(string $file, array &$errors): ?DatagramSocket
    {
        $endpoint = $this->resolveEndpoint($file);
        if ($endpoint === null) {
            DatagramSocket::silenced(static fn() => unlink($file));
            return null;
        }

        $socket = DatagramSocket::connect($endpoint[0], $endpoint[1], $this->timeout);
        if ($socket->isOpen()) {
            return $socket;
        }

        // Nobody is bound behind the file (Unix reports ECONNREFUSED; UDP cannot tell,
        // so a failed connect is treated as stale too) — drop it so it is not retried.
        if ($socket->errno === SOCKET_ECONNREFUSED || Connection::isWindows()) {
            DatagramSocket::silenced(static fn() => unlink($file));
        }
        if ($socket->errno !== 0 && $socket->errno !== SOCKET_ECONNREFUSED) {
            $errors[$socket->errno] = $socket->errstr;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: int}|null `[address, port]`, or `null` for an unusable discovery file.
     */
    private function resolveEndpoint(string $file): ?array
    {
        if (!Connection::isWindows()) {
            return ['udg://' . $file, -1];
        }

        $portStr = DatagramSocket::silenced(static fn() => file_get_contents($file));
        $port = $portStr === false ? 0 : (int) $portStr;

        return $port > 0 ? ['udp://127.0.0.1', $port] : null;
    }
}
