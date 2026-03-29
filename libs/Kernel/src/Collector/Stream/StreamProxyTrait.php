<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Stream;

use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper;

use const SEEK_SET;

/**
 * Shared passthrough methods for stream wrapper proxies.
 *
 * PHP stream wrappers require implementing all protocol methods.
 * This trait provides the delegation boilerplate that is identical
 * between HttpStreamProxy and FilesystemStreamProxy.
 */
trait StreamProxyTrait
{
    abstract public static function register(): void;

    abstract public static function unregister(): void;

    public static bool $registered = false;
    /** @var resource|null */
    public $context;
    public StreamWrapper $decorated;
    public bool $ignored = false;

    public function __construct()
    {
        $this->decorated = new StreamWrapper();
        $this->decorated->context = $this->context;
    }

    public function __call(string $name, array $arguments)
    {
        try {
            static::unregister();
            return $this->decorated->{$name}(...$arguments);
        } finally {
            static::register();
        }
    }

    public function __get(string $name)
    {
        return $this->decorated->{$name};
    }

    public function stream_set_option(int $option, int $arg1, ?int $arg2): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_tell(): int
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_eof(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_cast(int $castAs): mixed
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_stat(): array|false
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_closedir(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_opendir(string $path, int $options): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_rewinddir(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_close(): void
    {
        $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_flush(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_lock(int $operation): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_truncate(int $new_size): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
