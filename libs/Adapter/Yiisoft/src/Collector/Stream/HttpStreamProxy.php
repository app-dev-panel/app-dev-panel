<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Stream;

use Yiisoft\Strings\CombinedRegexp;
use Yiisoft\Yii\Debug\Helper\BacktraceIgnoreMatcher;
use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapper;
use Yiisoft\Yii\Debug\Helper\StreamWrapper\StreamWrapperInterface;

use function stream_get_wrappers;

use const SEEK_SET;

final class HttpStreamProxy implements StreamWrapperInterface
{
    public static bool $registered = false;
    public static array $ignoredPathPatterns = [];
    public static array $ignoredClasses = [];
    public static array $ignoredUrls = [];
    /**
     * @var resource|null
     */
    public $context;
    public StreamWrapper $decorated;
    public bool $ignored = false;

    public static ?HttpStreamCollector $collector = null;
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
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', self::class, STREAM_IS_URL);

        if (in_array('https', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('https');
            stream_wrapper_register('https', self::class, STREAM_IS_URL);
        }

        self::$registered = true;
    }

    public static function unregister(): void
    {
        if (!self::$registered) {
            return;
        }
        @stream_wrapper_restore('http');
        @stream_wrapper_restore('https');
        self::$registered = false;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->ignored = $this->isIgnored($path);
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_read(int $count): string|false
    {
        if (!$this->ignored) {
            /**
             * @psalm-suppress PossiblyNullArgument
             */
            $metadata = stream_get_meta_data($this->decorated->stream);
            $context = $this->decorated->context === null
                ? null
                : stream_context_get_options($this->decorated->context);
            /**
             * @link https://www.php.net/manual/en/context.http.php
             */
            $method = $context['http']['method'] ?? $context['https']['method'] ?? 'GET';
            $headers = (array) ($context['http']['header'] ?? $context['https']['header'] ?? []);

            $this->operations['read'] = [
                'path' => $this->decorated->filename,
                'args' => [
                    'method' => $method,
                    'response_headers' => $metadata['wrapper_data'],
                    'request_headers' => $headers,
                ],
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
            $this->operations[__FUNCTION__] = [
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
        if (!$this->ignored) {
            $this->operations[__FUNCTION__] = [
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
        if (!$this->ignored) {
            $this->operations[__FUNCTION__] = [
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
        if (!$this->ignored) {
            $this->operations[__FUNCTION__] = [
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
        if (!$this->ignored) {
            $this->operations[__FUNCTION__] = [
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

    private function isIgnored(string $url): bool
    {
        if (BacktraceIgnoreMatcher::doesStringMatchPattern($url, self::$ignoredUrls)) {
            return true;
        }

        $backtrace = debug_backtrace();
        return BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, self::$ignoredClasses)
            || BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, self::$ignoredPathPatterns);
    }
}
