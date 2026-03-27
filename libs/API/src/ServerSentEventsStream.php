<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

use Closure;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStream implements StreamInterface, \Stringable
{
    public array $buffer = [];
    private bool $eof = false;

    /**
     * @param Closure $stream Callback that populates buffer and returns true to continue
     */
    public function __construct(
        private Closure $stream,
        private int $pollIntervalMicros = 500_000,
        private int $sleepChunkMicros = 50_000,
    ) {}

    public function close(): void
    {
        $this->eof = true;
    }

    public function detach(): mixed
    {
        $this->eof = true;
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
            $this->eof = true;
            return $output;
        }

        if ($this->pollIntervalMicros <= 0) {
            return $output;
        }

        // Interruptible sleep: split into small chunks so connection abort is detected quickly
        $slept = 0;
        while ($slept < $this->pollIntervalMicros) {
            if ($this->eof || connection_aborted()) {
                $this->eof = true;
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
