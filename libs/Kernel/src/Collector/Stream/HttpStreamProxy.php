<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Stream;

use AppDevPanel\Kernel\Helper\BacktraceIgnoreMatcher;
use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper;
use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapperInterface;
use Yiisoft\Strings\CombinedRegexp;

use function stream_get_wrappers;

final class HttpStreamProxy implements StreamWrapperInterface
{
    use StreamProxyTrait;

    public static array $ignoredPathPatterns = [];
    public static array $ignoredClasses = [];
    public static array $ignoredUrls = [];
    public static ?HttpStreamCollector $collector = null;

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

            self::$collector?->collect(operation: 'read',
                path: $this->decorated->filename,
                args: [
                    'method' => $method,
                    'response_headers' => $metadata['wrapper_data'],
                    'request_headers' => $headers,
                ],
            );
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function dir_readdir(): false|string
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'readdir',
                path: $this->decorated->filename,
                args: [],
            );
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'mkdir',
                path: $path,
                args: [
                    'mode' => $mode,
                    'options' => $options,
                ],
            );
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rename(string $path_from, string $path_to): bool
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'rename',
                path: $path_from,
                args: [
                    'path_to' => $path_to,
                ],
            );
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function rmdir(string $path, int $options): bool
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'rmdir',
                path: $path,
                args: [
                    'options' => $options,
                ],
            );
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function stream_write(string $data): int
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'write',
                path: $this->decorated->filename,
                args: [],
            );
        }

        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function unlink(string $path): bool
    {
        if (!$this->ignored) {
            self::$collector?->collect(operation: 'unlink', path: $path, args: []);
        }
        return $this->__call(__FUNCTION__, func_get_args());
    }

    private function isIgnored(string $url): bool
    {
        if (BacktraceIgnoreMatcher::doesStringMatchPattern($url, self::$ignoredUrls)) {
            return true;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        return (
            BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, self::$ignoredClasses)
            || BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, self::$ignoredPathPatterns)
        );
    }
}
