<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Support\Stub;

use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapper;
use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapperInterface;

final class PhpStreamProxy implements StreamWrapperInterface
{
    public static bool $registered = false;
    /**
     * @var resource|null
     */
    public $context;
    public StreamWrapperInterface $decorated;
    public bool $ignored = false;

    public array $operations = [];

    public function __construct()
    {
        $this->decorated = new StreamWrapper();
        $this->decorated->context = $this->context;
    }

    public function __destruct()
    {
        self::unregister();
    }

    public function __call(string $name, array $arguments)
    {
        try {
            self::unregister();
            return $this->decorated->{$name}(...$arguments);
        } finally {
            self::register();
        }
    }

    public function __get(string $name)
    {
        return $this->decorated->{$name};
    }

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        /**
         * It's important to trigger autoloader before unregistering the file stream handler
         */
        class_exists(StreamWrapper::class);
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', self::class, STREAM_IS_URL);

        self::$registered = true;
    }

    public static function unregister(): void
    {
        if (!self::$registered) {
            return;
        }
        @stream_wrapper_restore('php');
        self::$registered = false;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_read(int $count): string|false
    {
        return $this->__call(__FUNCTION__, func_get_args());
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

    public function stream_cast(int $castAs)
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

    public function dir_readdir(): false|string
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_rewinddir(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rename(string $path_from, string $path_to): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rmdir(string $path, int $options): bool
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

    public function stream_write(string $data): int
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function unlink(string $path): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
