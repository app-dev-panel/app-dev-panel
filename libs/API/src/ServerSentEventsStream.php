<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

use Closure;
use Generator;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStream implements StreamInterface, \Stringable
{
    /** @var array<int, string> */
    private array $buffer = [];
    private bool $eof = false;
    private ?Generator $generator = null;

    /**
     * @param Closure $stream Callback-based stream (legacy): fn(array &$buffer): bool
     */
    public function __construct(
        private readonly Closure $stream,
    ) {}

    /**
     * Create a stream from a generator-returning closure.
     *
     * The closure must return a Generator that yields SSE data strings.
     * Yielding an empty string signals end-of-stream.
     *
     * @param Closure(): Generator<int, string> $factory
     */
    public static function fromGenerator(Closure $factory): self
    {
        $instance = new self(static fn(): bool => false);
        $instance->generator = $factory();

        return $instance;
    }

    public function close(): void
    {
        $this->eof = true;
    }

    public function detach(): null
    {
        $this->eof = true;

        return null;
    }

    public function getSize(): ?int
    {
        return null;
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

    public function read(int $length): string
    {
        if ($this->generator !== null) {
            return $this->readFromGenerator();
        }

        return $this->readFromCallback();
    }

    public function getContents(): string
    {
        return $this->read(8_388_608);
    }

    /**
     * @return mixed[]|null
     */
    public function getMetadata($key = null): ?array
    {
        if ($key !== null) {
            return null;
        }

        return [];
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    private function readFromGenerator(): string
    {
        if (!$this->generator->valid()) {
            $this->eof = true;

            return '';
        }

        $message = $this->generator->current();
        $this->generator->next();

        if ($message === '' || $message === null) {
            $this->eof = true;

            return '';
        }

        return sprintf("data: %s\n\n", $message);
    }

    private function readFromCallback(): string
    {
        $continue = ($this->stream)($this->buffer);

        if (!$continue) {
            $this->eof = true;
        }

        $output = '';
        foreach ($this->buffer as $key => $value) {
            unset($this->buffer[$key]);
            $output .= sprintf("data: %s\n", $value);
        }
        $output .= "\n";

        return $output;
    }
}
