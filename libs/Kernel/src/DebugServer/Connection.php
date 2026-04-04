<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use RuntimeException;
use Socket;

/**
 * Cross-platform debug server connection.
 *
 * - Linux/macOS: AF_UNIX SOCK_DGRAM (Unix domain sockets)
 * - Windows: AF_INET SOCK_DGRAM on 127.0.0.1 (UDP localhost)
 *
 * Discovery: socket files in sys_get_temp_dir():
 * - Unix: adp-debug-server-{id}.sock
 * - Windows: adp-debug-server-{port}.port (contains port number)
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
    public const MESSAGE_TYPE_ENTRY_CREATED = 0x003B;

    public const SOCKET_FILE_PREFIX = 'adp-debug-server-';

    private string $uri;
    private bool $closed = false;

    public function __construct(
        private readonly Socket $socket,
    ) {}

    public static function create(): self
    {
        if (self::isWindows()) {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        } else {
            $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        }

        if ($socket === false) {
            throw new RuntimeException(sprintf('Failed to create socket: "%s".', socket_strerror(socket_last_error())));
        }

        $socketLastError = socket_last_error($socket);
        if ($socketLastError) {
            throw new RuntimeException(sprintf(
                '"socket_last_error" returned %d: "%s".',
                $socketLastError,
                socket_strerror($socketLastError),
            ));
        }

        return new self($socket);
    }

    public function bind(): void
    {
        if (self::isWindows()) {
            $this->bindTcp();
        } else {
            $this->bindUnix();
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
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        if (self::isWindows()) {
            $this->closeWindows();
        } else {
            $this->closeUnix();
        }
    }

    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Returns glob pattern for discovering all running debug servers.
     */
    public static function discoveryPattern(): string
    {
        if (self::isWindows()) {
            return sys_get_temp_dir() . '/' . self::SOCKET_FILE_PREFIX . '*.port';
        }

        return sys_get_temp_dir() . '/' . self::SOCKET_FILE_PREFIX . '*.sock';
    }

    private function bindUnix(): void
    {
        $n = random_int(0, PHP_INT_MAX);
        $file = sprintf('%s/%s%d.sock', sys_get_temp_dir(), self::SOCKET_FILE_PREFIX, $n);
        $this->uri = $file;

        if (!socket_bind($this->socket, $file)) {
            $socketLastError = socket_last_error($this->socket);

            throw new RuntimeException(sprintf(
                'An error occurred while binding the socket. "socket_last_error" returned %d: "%s".',
                $socketLastError,
                socket_strerror($socketLastError),
            ));
        }
    }

    private function bindTcp(): void
    {
        if (!socket_bind($this->socket, '127.0.0.1', 0)) {
            $socketLastError = socket_last_error($this->socket);

            throw new RuntimeException(sprintf(
                'An error occurred while binding the socket. "socket_last_error" returned %d: "%s".',
                $socketLastError,
                socket_strerror($socketLastError),
            ));
        }

        $address = '';
        $port = 0;
        socket_getsockname($this->socket, $address, $port);

        $file = sprintf('%s/%s%d.port', sys_get_temp_dir(), self::SOCKET_FILE_PREFIX, $port);
        $this->uri = $file;

        if (file_put_contents($file, (string) $port) === false) {
            @socket_close($this->socket);
            throw new RuntimeException(sprintf('Failed to write discovery file: "%s".', $file));
        }
    }

    private function closeUnix(): void
    {
        $path = null;
        @socket_getsockname($this->socket, $path);
        @socket_close($this->socket);
        if ($path !== null) {
            @unlink($path);
        }
    }

    private function closeWindows(): void
    {
        @socket_close($this->socket);
        if (isset($this->uri)) {
            @unlink($this->uri);
        }
    }
}
