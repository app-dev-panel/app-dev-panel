<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

use Closure;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStream implements StreamInterface, \Stringable
{
    public array $buffer = [];
    private bool $eof = false;
    private readonly int $parentPid;

    /**
     * @param Closure $stream Callback that populates buffer and returns true to continue
     * @param null|Closure $onClose Optional cleanup callback invoked when stream ends
     */
    public function __construct(
        private Closure $stream,
        private int $pollIntervalMicros = 500_000,
        private int $sleepChunkMicros = 50_000,
        private ?Closure $onClose = null,
    ) {
        $this->parentPid = function_exists('posix_getppid') ? posix_getppid() : 0;
        $this->installSignalHandler();
    }

    private function installSignalHandler(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);
        $handler = function (): void {
            $this->eof = true;
        };
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);
    }

    /**
     * Detect if the parent process has died (worker became orphan).
     * Orphaned processes are reparented to PID 1 (init/systemd).
     */
    private function isOrphaned(): bool
    {
        if ($this->parentPid === 0 || !function_exists('posix_getppid')) {
            return false;
        }

        return posix_getppid() !== $this->parentPid;
    }

    public function close(): void
    {
        if (!$this->eof) {
            $this->eof = true;
            if ($this->onClose !== null) {
                ($this->onClose)();
            }
        }
    }

    public function detach(): mixed
    {
        $this->close();
        return null;
    }

    public function getSize(): int
    {
        return 0;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    /**
     * TODO: support length reading
     */
    public function read(int $length): string
    {
        if ($this->eof) {
            return '';
        }

        $continue = ($this->stream)($this->buffer);

        $output = '';
        foreach ($this->buffer as $key => $value) {
            unset($this->buffer[$key]);
            $output .= sprintf("data: %s\n", $value);
        }
        $output .= "\n";

        if (!$continue || $this->eof) {
            $this->close();
            return $output;
        }

        if ($this->pollIntervalMicros <= 0) {
            return $output;
        }

        // Interruptible sleep: split into small chunks so connection abort is detected quickly
        $slept = 0;
        while ($slept < $this->pollIntervalMicros) {
            if ($this->eof || connection_aborted() || $this->isOrphaned()) {
                $this->close();
                return $output;
            }
            usleep($this->sleepChunkMicros);
            $slept += $this->sleepChunkMicros;
        }

        return $output;
    }

    public function getContents(): string
    {
        return $this->read(1024);
    }

    public function getMetadata($key = null): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->getContents();
    }
}
