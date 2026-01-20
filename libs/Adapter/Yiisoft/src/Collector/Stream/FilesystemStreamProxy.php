<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Stream;

use Yiisoft\Strings\CombinedRegexp;
use Yiisoft\Yii\Debug\Helper\BacktraceIgnoreMatcher;
use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapper;
use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapperInterface;

use const SEEK_SET;

final class FilesystemStreamProxy implements StreamWrapperInterface
{
    public static bool $registered = false;
    /**
     * @var resource|null
     */
    public $context;
    public StreamWrapper $decorated;
    public bool $ignored = false;

    public static ?FilesystemStreamCollector $collector = null;
    public static array $ignoredPathPatterns = [];
    public static array $ignoredClasses = [];
    public array $operations = [];

    public function __construct()
    {
        $this->decorated = new StreamWrapper();
        $this->decorated->context = $this->context;
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

    public function __destruct()
    {
        if (self::$collector === null) {
            return;
        }
        foreach ($this->operations as $name => $operation) {
            self::$collector->collect(
                operation: $name,
                path: $operation['path'],
                args: $operation['args'],
            );
        }
        self::unregister();
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
        class_exists(BacktraceIgnoreMatcher::class);
        class_exists(StreamWrapper::class);
        class_exists(CombinedRegexp::class);
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class, STREAM_IS_URL);
        self::$registered = true;
    }

    public static function unregister(): void
    {
        if (!self::$registered) {
            return;
        }
        @stream_wrapper_restore('file');
        self::$registered = false;
    }

    private function isIgnored(): bool
    {
        $backtrace = debug_backtrace();
        return BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, self::$ignoredClasses)
            || BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, self::$ignoredPathPatterns);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->ignored = $this->isIgnored();
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_read(int $count): string|false
    {
        if (!$this->ignored) {
            $this->operations['read'] = [
                'path' => $this->decorated->filename,
                'args' => [],
            ];
        }
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
        if (!$this->ignored) {
            $this->operations['readdir'] = [
                'path' => $this->decorated->filename,
                'args' => [],
            ];
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_rewinddir(): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        if (!$this->isIgnored()) {
            $this->operations['mkdir'] = [
                'path' => $path,
                'args' => [
                    'mode' => $mode,
                    'options' => $options,
                ],
            ];
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rename(string $path_from, string $path_to): bool
    {
        if (!$this->isIgnored()) {
            $this->operations['rename'] = [
                'path' => $path_from,
                'args' => [
                    'path_to' => $path_to,
                ],
            ];
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rmdir(string $path, int $options): bool
    {
        if (!$this->isIgnored()) {
            $this->operations['rmdir'] = [
                'path' => $path,
                'args' => [
                    'options' => $options,
                ],
            ];
        }
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
        if (!$this->ignored) {
            $this->operations['write'] = [
                'path' => $this->decorated->filename,
                'args' => [],
            ];
        }

        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function unlink(string $path): bool
    {
        if (!$this->isIgnored()) {
            $this->operations['unlink'] = [
                'path' => $path,
                'args' => [],
            ];
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
