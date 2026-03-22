<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Generator;
use Socket;

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
        $sndbuf = socket_get_option($this->socket, SOL_SOCKET, SO_SNDBUF);
        $rcvbuf = socket_get_option($this->socket, SOL_SOCKET, SO_RCVBUF);

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 10);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 10);

        $newFrameAwaitRepeat = 0;
        $maxFrameAwaitRepeats = 10;
        $maxRepeats = 10;

        while (true) {
            if (!socket_recv($this->socket, $header, 8, MSG_WAITALL)) {
                $socket_last_error = socket_last_error($this->socket);
                $newFrameAwaitRepeat++;
                if ($newFrameAwaitRepeat === $maxFrameAwaitRepeats) {
                    $newFrameAwaitRepeat = 0;
                    yield [Connection::TYPE_RELEASE, $socket_last_error, socket_strerror($socket_last_error)];
                }
                if ($socket_last_error === self::SOCKET_EAGAIN) {
                    usleep(Connection::DEFAULT_TIMEOUT);
                    continue;
                }
                $this->closeSocket();
                yield [Connection::TYPE_ERROR, $socket_last_error, socket_strerror($socket_last_error)];
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
                    MSG_DONTWAIT,
                );
                if ($bufferLength === false) {
                    if ($repeat === $maxRepeats) {
                        break;
                    }
                    $socket_last_error = socket_last_error($this->socket);
                    if ($socket_last_error === self::SOCKET_EAGAIN) {
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

    private const SOCKET_EAGAIN = 35;

    private function closeSocket(): void
    {
        @socket_getsockname($this->socket, $path);
        @socket_close($this->socket);
        @unlink($path);
    }
}
