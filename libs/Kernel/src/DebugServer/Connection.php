<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use RuntimeException;
use Socket;

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
    ) {}

    public static function create(): self
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);

        $socket_last_error = socket_last_error($socket);

        if ($socket_last_error) {
            throw new RuntimeException(sprintf(
                '"socket_last_error" returned %d: "%s".',
                $socket_last_error,
                socket_strerror($socket_last_error),
            ));
        }

        return new self($socket);
    }

    public function bind(): void
    {
        $n = random_int(0, PHP_INT_MAX);
        $file = sprintf(sys_get_temp_dir() . '/adp-dev-server-%d.sock', $n);
        $this->uri = $file;
        if (!socket_bind($this->socket, $file)) {
            $socket_last_error = socket_last_error($this->socket);

            throw new RuntimeException(sprintf(
                'An error occurred while reading the socket. "socket_last_error" returned %d: "%s".',
                $socket_last_error,
                socket_strerror($socket_last_error),
            ));
        }
    }

    public function getSocket(): Socket
    {
        return $this->socket;
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
}
