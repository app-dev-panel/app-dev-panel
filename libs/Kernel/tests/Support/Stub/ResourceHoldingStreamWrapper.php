<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Support\Stub;

/**
 * Minimal user-space stream wrapper whose instance owns a raw resource.
 *
 * Streams opened through it expose the wrapper object under
 * `stream_get_meta_data()['wrapper_data']` — exactly the shape that made
 * `json_encode()` fail with "Type is not supported" (issue #114).
 */
final class ResourceHoldingStreamWrapper
{
    public const string SCHEME = 'adp-resource-holder';

    /** @var resource|null */
    public $context;

    /** @var resource|null */
    public $inner = null;

    public static function register(): void
    {
        if (!in_array(self::SCHEME, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::SCHEME, self::class);
        }
    }

    public static function unregister(): void
    {
        if (in_array(self::SCHEME, stream_get_wrappers(), true)) {
            stream_wrapper_unregister(self::SCHEME);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->inner = fopen('php://memory', 'r+');
        fwrite($this->inner, 'payload');
        rewind($this->inner);

        return true;
    }

    public function stream_read(int $count): string|false
    {
        return $this->inner === null ? false : fread($this->inner, $count);
    }

    public function stream_write(string $data): int
    {
        return $this->inner === null ? 0 : (int) fwrite($this->inner, $data);
    }

    public function stream_eof(): bool
    {
        return $this->inner === null || feof($this->inner);
    }

    public function stream_stat(): array|false
    {
        return $this->inner === null ? false : fstat($this->inner);
    }

    public function stream_close(): void
    {
        if ($this->inner !== null) {
            fclose($this->inner);
            $this->inner = null;
        }
    }
}
