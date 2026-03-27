<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Stream;

use AppDevPanel\Kernel\Helper\BacktraceIgnoreMatcher;
use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapper;
use AppDevPanel\Kernel\Helper\StreamWrapper\StreamWrapperInterface;
use Yiisoft\Strings\CombinedRegexp;

final class FilesystemStreamProxy implements StreamWrapperInterface
{
    use StreamProxyTrait;

    public static ?FilesystemStreamCollector $collector = null;
    public static array $ignoredPathPatterns = [];
    public static array $ignoredClasses = [];

    public function __destruct()
    {
        if (self::$collector === null) {
            return;
        }
        $this->flushOperationsToCollector();
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
        stream_wrapper_register('file', self::class);
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

    private function isIgnored(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        return (
            BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, self::$ignoredClasses)
            || BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, self::$ignoredPathPatterns)
        );
    }
}
