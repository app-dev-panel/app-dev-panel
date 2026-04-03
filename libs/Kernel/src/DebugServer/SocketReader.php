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
 * and handles MSG_DONTWAIT absence on Windows.
 */
final class SocketReader
{
    public function __construct(
        private readonly Socket $socket,
    ) {}

    /**
     * @return Generator<int, array{0: Connection::TYPE_ERROR|Connection::TYPE_RELEASE|Connection::TYPE_RESULT, 1: string, 2: int|string, 3?: int}>
     */
    public function read(): Generator
    {
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 10);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 10);

        $eagain = self::eagainErrorCode();
        $recvFlags = self::nonBlockingRecvFlags();

        $newFrameAwaitRepeat = 0;
        $maxFrameAwaitRepeats = 10;
        $maxRepeats = 10;

        while (true) {
            if (!socket_recv($this->socket, $header, 8, MSG_WAITALL)) {
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
                $this->closeSocket();
                yield [Connection::TYPE_ERROR, $socketLastError, socket_strerror($socketLastError)];
                continue;
            }

            $length = unpack('P', $header);
            $localBuffer = '';
            $bytesToRead = $length[1];
            $bytesRead = 0;
            $repeat = 0;
            while ($bytesRead < $bytesToRead) {
                $bufferLength = socket_recv(
                    $this->socket,
                    $buffer,
                    min($bytesToRead - $bytesRead, Connection::DEFAULT_BUFFER_SIZE),
                    $recvFlags,
                );
                if ($bufferLength === false) {
                    if ($repeat === $maxRepeats) {
                        break;
                    }
                    $socketLastError = socket_last_error($this->socket);
                    if ($socketLastError === $eagain) {
                        $repeat++;
                        usleep(Connection::DEFAULT_TIMEOUT * 5);
                        continue;
                    }
                    $this->closeSocket();
                    break;
                }

                $localBuffer .= $buffer;
                $bytesRead += $bufferLength;
            }
            yield [Connection::TYPE_RESULT, base64_decode($localBuffer, true)];
        }
    }

    /**
     * Returns the platform-correct EAGAIN/EWOULDBLOCK error code.
     *
     * - Linux: SOCKET_EAGAIN = 11
     * - macOS: SOCKET_EAGAIN = 35
     * - Windows: SOCKET_EWOULDBLOCK = 10035
     */
    private static function eagainErrorCode(): int
    {
        // PHP's sockets extension defines SOCKET_EAGAIN with the correct OS value
        if (defined('SOCKET_EAGAIN')) {
            return SOCKET_EAGAIN;
        }
        // Fallback for Windows where EAGAIN maps to EWOULDBLOCK
        if (defined('SOCKET_EWOULDBLOCK')) {
            return SOCKET_EWOULDBLOCK;
        }

        // Last resort: detect OS
        return PHP_OS_FAMILY === 'Darwin' ? 35 : 11;
    }

    /**
     * Returns flags for non-blocking socket_recv.
     *
     * MSG_DONTWAIT is not available on Windows; use socket_set_nonblock() instead.
     */
    private static function nonBlockingRecvFlags(): int
    {
        if (defined('MSG_DONTWAIT')) {
            return MSG_DONTWAIT;
        }

        // On Windows, MSG_DONTWAIT is not available.
        // Return 0 — callers should use socket_set_nonblock() on the socket.
        return 0;
    }

    private function closeSocket(): void
    {
        $path = null;
        @socket_getsockname($this->socket, $path);
        @socket_close($this->socket);
        if ($path !== null && file_exists($path)) {
            unlink($path);
        }
    }
}
