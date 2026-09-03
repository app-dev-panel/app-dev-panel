<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use Psr\Http\Message\StreamInterface;

/**
 * Reads a PSR-7 body for display without ever consuming or blowing up on it:
 * size-capped (also for unknown-length streams), non-seekable / non-readable
 * streams are described instead of read, the position is restored afterwards,
 * and a throwing stream yields a placeholder rather than an exception.
 */
final class StreamBodyReader
{
    private const int READ_CHUNK = 65_536;

    public function __construct(
        private readonly int $maxBodySize,
    ) {}

    public function read(StreamInterface $body): string
    {
        try {
            $size = $body->getSize();
            if ($size !== null && $size > $this->maxBodySize) {
                return sprintf('[body omitted: %d bytes exceeds %d byte limit]', $size, $this->maxBodySize);
            }

            if (!$body->isSeekable() || !$body->isReadable()) {
                return sprintf(
                    '[body omitted: stream is not %s, %s]',
                    $body->isReadable() ? 'seekable' : 'readable',
                    $size === null ? 'unknown size' : $size . ' bytes',
                );
            }

            $position = $body->tell();
            $body->rewind();
            $content = $this->readLimited($body);
            $body->seek($position);
        } catch (\Throwable $e) {
            return sprintf('[body unavailable: %s]', $e->getMessage());
        }

        return $content;
    }

    /**
     * Reads at most `maxBodySize` + 1 bytes so an unknown-length stream is capped too.
     */
    private function readLimited(StreamInterface $body): string
    {
        $content = '';
        $limit = $this->maxBodySize + 1;
        while (!$body->eof() && strlen($content) < $limit) {
            $chunk = $body->read(min(self::READ_CHUNK, $limit - strlen($content)));
            if ($chunk === '') {
                break;
            }
            $content .= $chunk;
        }

        if (strlen($content) > $this->maxBodySize) {
            return sprintf('[body omitted: exceeds %d byte limit]', $this->maxBodySize);
        }

        return $content;
    }
}
