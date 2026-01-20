<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\DebugServer;

use Generator;
use RuntimeException;
use Socket;
use Throwable;

/**
 * List of socket errors: {@see https://www.ibm.com/docs/en/zos/2.4.0?topic=calls-sockets-return-codes-errnos}
 */
final class Connection
{
    public const DEFAULT_TIMEOUT = 10 * 1000; // 10 milliseconds
    public const DEFAULT_BUFFER_SIZE = 1 * 1024; // 1 kilobyte

    public const TYPE_RESULT = 0x001B;
    public const TYPE_ERROR = 0x002B;
    public const TYPE_RELEASE = 0x003B;

    public const MESSAGE_TYPE_VAR_DUMPER = 0x001B;
    public const MESSAGE_TYPE_LOGGER = 0x002B;

    private string $uri;

    public function __construct(
        private readonly Socket $socket,
    ) {
    }

    public static function create(): self
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);

        $socket_last_error = socket_last_error($socket);

        if ($socket_last_error) {
            throw new RuntimeException(
                sprintf(
                    '"socket_last_error" returned %d: "%s".',
                    $socket_last_error,
                    socket_strerror($socket_last_error),
                ),
            );
        }

        return new self(
            $socket,
        );
    }

    public function bind(): void
    {
        $n = random_int(0, PHP_INT_MAX);
        $file = sprintf(sys_get_temp_dir() . '/yii-dev-server-%d.sock', $n);
        $this->uri = $file;
        if (!socket_bind($this->socket, $file)) {
            $socket_last_error = socket_last_error($this->socket);

            throw new RuntimeException(
                sprintf(
                    'An error occurred while reading the socket. "socket_last_error" returned %d: "%s".',
                    $socket_last_error,
                    socket_strerror($socket_last_error),
                ),
            );
        }
    }

    /**
     * @return Generator<int, array{0: self::TYPE_ERROR|self::TYPE_RELEASE|self::TYPE_RESULT, 1: string, 2: int|string, 3?: int}>
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
                    yield [self::TYPE_RELEASE, $socket_last_error, socket_strerror($socket_last_error)];
                }
                if ($socket_last_error === 35) {
                    usleep(self::DEFAULT_TIMEOUT);
                    continue;
                }
                $this->close();
                yield [self::TYPE_ERROR, $socket_last_error, socket_strerror($socket_last_error)];
                continue;
            }

            $length = unpack('P', (string) $header);
            $localBuffer = '';
            $bytesToRead = $length[1];
            $bytesRead = 0;
            //$value = 2 ** ((int) ($bytesToRead / 2));
            //socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, $value);
            $repeat = 0;
            while ($bytesRead < $bytesToRead) {
                //$buffer = socket_read($this->socket,  $bytesToRead - $bytesRead);
                //$bufferLength = strlen($buffer);
                $bufferLength = socket_recv($this->socket, $buffer, min($bytesToRead - $bytesRead, self::DEFAULT_BUFFER_SIZE), MSG_DONTWAIT);
                if ($bufferLength === false) {
                    if ($repeat === $maxRepeats) {
                        break;
                    }
                    //if ($bufferLength === false) {
                    $socket_last_error = socket_last_error($this->socket);
                    if ($socket_last_error === 35) {
                        $repeat++;
                        usleep(self::DEFAULT_TIMEOUT * 5);
                        continue;
                    }
                    $this->close();
                    break;
                }

                $localBuffer .= $buffer;
                $bytesRead += $bufferLength;
            }
            yield [self::TYPE_RESULT, base64_decode($localBuffer)];
        }
    }

    public function broadcast(int $type, string $data): array
    {
        $files = glob(sys_get_temp_dir() . '/yii-dev-server-*.sock', GLOB_NOSORT);
        //echo 'Files: ' . implode(', ', $files) . "\n";
        $uniqueErrors = [];
        $payload = json_encode([$type, $data], JSON_THROW_ON_ERROR);
        foreach ($files as $file) {
            $socket = @fsockopen('udg://' . $file, -1, $errno, $errstr);
            if ($errno === 61) {
                @unlink($file);
                continue;
            }
            if ($errno !== 0) {
                $uniqueErrors[$errno] = $errstr;
                continue;
            }
            try {
                if (!$this->fwriteStream($socket, $payload)) {
                    $uniqueErrors[] = error_get_last();
                    /**
                     * Connection is closed.
                     */
                    continue;
                }
            } catch (Throwable $e) {
                //@unlink($file);
                throw $e;
            } finally {
                //fflush($socket);
                fclose($socket);
            }
        }
        return $uniqueErrors;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function close(): void
    {
        @socket_getsockname($this->socket, $path);
        @socket_close($this->socket);
        @unlink($path);
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
            $fwrite = fwrite($fp, substr($data, $written), self::DEFAULT_BUFFER_SIZE);
            //\fflush($fp);
            usleep(self::DEFAULT_TIMEOUT * 5);
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }
}
