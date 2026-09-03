<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

/**
 * A connected datagram socket (`udg://` on Unix, `udp://` on Windows) with
 * bounded, notice-free I/O.
 *
 * - `connect()` passes the timeout to `fsockopen()` and never emits warnings.
 * - `send()` switches the stream to non-blocking mode so a receiver with a full
 *   buffer yields EAGAIN (reported as `0` bytes) instead of blocking for
 *   `default_socket_timeout`; it retries until the deadline, waiting for
 *   writability via `stream_select()`.
 * - Any PHP notice raised by the stream layer is captured and returned as an
 *   error string instead of being printed.
 */
final class DatagramSocket
{
    /**
     * @param resource|null $stream
     */
    private function __construct(
        private $stream,
        public readonly int $errno,
        public readonly string $errstr,
        private readonly float $timeout,
    ) {}

    public static function connect(string $address, int $port, float $timeout): self
    {
        $errno = 0;
        $errstr = '';
        $stream = self::silenced(static function () use ($address, $port, &$errno, &$errstr, $timeout) {
            return fsockopen($address, $port, $errno, $errstr, $timeout);
        });

        return new self($stream === false ? null : $stream, $errno, $errstr, $timeout);
    }

    public function isOpen(): bool
    {
        return $this->stream !== null && $this->errno === 0;
    }

    /**
     * Sends one datagram atomically (correct for SOCK_DGRAM).
     *
     * @return string|null Error description, or `null` on success.
     */
    public function send(string $datagram): ?string
    {
        $fp = $this->stream;
        if ($fp === null) {
            return 'socket is not open';
        }

        stream_set_write_buffer($fp, 0);
        stream_set_blocking($fp, false);
        $deadline = microtime(true) + $this->timeout;

        do {
            $notice = null;
            $written = self::silenced(static fn() => fwrite($fp, $datagram), $notice);

            if ($written !== false && $written > 0) {
                return null;
            }
            if ($notice !== null) {
                // Non-transient error (e.g. EMSGSIZE) — the stream layer reported it.
                return $notice;
            }
        } while ($this->waitWritable($fp, $deadline));

        return sprintf('Send timed out after %.3fs (receiver buffer full)', $this->timeout);
    }

    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * Blocks until `$fp` is writable or the deadline passes.
     *
     * @param resource $fp
     *
     * @return bool `true` when another write attempt may be made.
     */
    private function waitWritable($fp, float $deadline): bool
    {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            return false;
        }

        $read = null;
        $except = null;
        $write = [$fp];
        $seconds = (int) floor($remaining);
        $micro = (int) (($remaining - $seconds) * 1_000_000);
        $ready = self::silenced(static fn() => stream_select($read, $write, $except, $seconds, $micro));

        return $ready !== false;
    }

    /**
     * Runs `$callback` with warnings/notices captured into `$message` instead of being emitted.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function silenced(callable $callback, ?string &$message = null): mixed
    {
        set_error_handler(static function (int $severity, string $text) use (&$message): bool {
            $message = $text;
            return true;
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
