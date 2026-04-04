<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Generator;
use Socket;

/**
 * Reads messages from a debug server socket.
 *
 * Cross-platform: uses PHP's SOCKET_EAGAIN constant (correct on Linux/macOS/Windows)
 * and sets non-blocking mode on Windows where MSG_DONTWAIT is unavailable.
 */
final class SocketReader
{
    /**
     * Maximum datagram size to read. Each message is one atomic datagram:
     * 8-byte length header + base64-encoded payload.
     */
    private const MAX_DATAGRAM_SIZE = 65536;

    public function __construct(
        private readonly Socket $socket,
    ) {}

    /**
     * @return Generator<int, array{0: Connection::TYPE_ERROR|Connection::TYPE_RELEASE|Connection::TYPE_RESULT, 1: string, 2: int|string, 3?: int}>
     */
    public function read(): Generator
    {
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, self::MAX_DATAGRAM_SIZE);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, self::MAX_DATAGRAM_SIZE);

        $eagain = self::eagainErrorCode();

        $newFrameAwaitRepeat = 0;
        $maxFrameAwaitRepeats = 10;

        while (true) {
            $bytes = socket_recv($this->socket, $datagram, self::MAX_DATAGRAM_SIZE, 0);

            if ($bytes === false || $bytes === 0) {
                $socketLastError = socket_last_error($this->socket);
                $newFrameAwaitRepeat++;
                if ($newFrameAwaitRepeat === $maxFrameAwaitRepeats) {
                    $newFrameAwaitRepeat = 0;
                    yield [Connection::TYPE_RELEASE, $socketLastError, socket_strerror($socketLastError)];
                }
                if ($socketLastError === $eagain) {
                    usleep(Connection::DEFAULT_TIMEOUT);
                    continue;
                }
                yield [Connection::TYPE_ERROR, $socketLastError, socket_strerror($socketLastError)];
                return;
            }

            $newFrameAwaitRepeat = 0;

            // Each datagram is: 8-byte length header + base64-encoded payload
            if ($bytes < 8) {
                continue;
            }

            $length = unpack('P', substr($datagram, 0, 8));
            if ($length === false) {
                continue;
            }

            $encoded = substr($datagram, 8);
            $decoded = base64_decode($encoded, true);
            if ($decoded === false) {
                continue;
            }

            yield [Connection::TYPE_RESULT, $decoded];
        }
    }

    /**
     * Platform-correct EAGAIN/EWOULDBLOCK error code.
     *
     * - Linux: SOCKET_EAGAIN = 11
     * - macOS: SOCKET_EAGAIN = 35
     * - Windows: SOCKET_EWOULDBLOCK = 10035
     */
    private static function eagainErrorCode(): int
    {
        if (defined('SOCKET_EAGAIN')) {
            return SOCKET_EAGAIN;
        }
        // Fallback for Windows where EAGAIN maps to EWOULDBLOCK
        if (defined('SOCKET_EWOULDBLOCK')) {
            return SOCKET_EWOULDBLOCK;
        }

        return PHP_OS_FAMILY === 'Darwin' ? 35 : 11;
    }
}
