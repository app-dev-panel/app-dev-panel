<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Helper\StreamWrapper;

use Throwable;

use function trigger_error;

use const E_USER_ERROR;
use const STREAM_MKDIR_RECURSIVE;
use const STREAM_URL_STAT_QUIET;
use const STREAM_USE_PATH;

final class StreamWrapper implements StreamWrapperInterface
{
    private const STREAM_OPEN_FOR_INCLUDE = 128;

    /**
     * @var resource|null
     */
    public mixed $context = null;

    public ?string $filename = null;

    /**
     * @var resource|null
     */
    public $stream = null;

    public function dir_closedir(): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        closedir($this->stream);

        /**
         * @psalm-suppress RedundantCondition
         */
        return is_resource($this->stream);
    }

    public function dir_opendir(string $path, int $options): bool
    {
        $this->filename = $path;

        /**
         * @psalm-suppress PossiblyNullArgument
         */
        $this->stream = opendir($path, $this->context);
        return is_resource($this->stream);
    }

    public function dir_readdir(): false|string
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return readdir($this->stream);
    }

    public function dir_rewinddir(): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }

        rewinddir($this->stream);

        /**
         * @noinspection PhpConditionAlreadyCheckedInspection
         * @psalm-suppress RedundantCondition
         */
        return is_resource($this->stream);
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        $this->filename = $path;

        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return mkdir($path, $mode, ($options & STREAM_MKDIR_RECURSIVE) === STREAM_MKDIR_RECURSIVE, $this->context);
    }

    public function rename(string $path_from, string $path_to): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return rename($path_from, $path_to, $this->context);
    }

    public function rmdir(string $path, int $options): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return rmdir($path, $this->context);
    }

    /**
     * @psalm-suppress InvalidReturnType Unfortunately, I don't know what to return here.
     */
    public function stream_cast(int $castAs): void
    {
        // ???
    }

    public function stream_eof(): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return feof($this->stream);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->filename = realpath($path) ?: $path;

        if ((self::STREAM_OPEN_FOR_INCLUDE & $options) === self::STREAM_OPEN_FOR_INCLUDE && function_exists(
            'opcache_invalidate'
        )) {
            opcache_invalidate($path, false);
        }
        $this->stream = fopen(
            $path,
            $mode,
            ($options & STREAM_USE_PATH) === STREAM_USE_PATH,
            (self::STREAM_OPEN_FOR_INCLUDE & $options) === self::STREAM_OPEN_FOR_INCLUDE ? null : $this->context
        );

        if (!is_resource($this->stream)) {
            return false;
        }

        if ($opened_path !== null) {
            $metaData = stream_get_meta_data($this->stream);
            $opened_path = $metaData['uri'];
        }
        return true;
    }

    public function stream_read(int $count): string|false
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return fread($this->stream, $count);
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return fseek($this->stream, $offset, $whence) !== -1;
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return match ($option) {
            STREAM_OPTION_BLOCKING => stream_set_blocking($this->stream, $arg1 === STREAM_OPTION_BLOCKING),
            STREAM_OPTION_READ_TIMEOUT => stream_set_timeout($this->stream, $arg1, $arg2),
            STREAM_OPTION_WRITE_BUFFER => stream_set_write_buffer($this->stream, $arg2) === 0,
            default => false,
        };
    }

    public function stream_stat(): array|false
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return fstat($this->stream);
    }

    public function stream_tell(): int
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return ftell($this->stream);
    }

    public function stream_write(string $data): int
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return fwrite($this->stream, $data);
    }

    public function url_stat(string $path, int $flags): array|false
    {
        try {
            if (($flags & STREAM_URL_STAT_QUIET) === STREAM_URL_STAT_QUIET) {
                return @stat($path);
            }
            return stat($path);
        } catch (Throwable $e) {
            if (($flags & STREAM_URL_STAT_QUIET) === STREAM_URL_STAT_QUIET) {
                return false;
            }
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        return match ($option) {
            STREAM_META_TOUCH => touch($path, ...$value),
            STREAM_META_OWNER_NAME, STREAM_META_OWNER => chown($path, $value),
            STREAM_META_GROUP_NAME, STREAM_META_GROUP => chgrp($path, $value),
            STREAM_META_ACCESS => chmod($path, $value),
            default => false
        };
    }

    public function stream_flush(): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return fflush($this->stream);
    }

    public function stream_close(): void
    {
        /**
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        if ($this->stream !== null) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    public function stream_lock(int $operation): bool
    {
        if ($operation === 0) {
            $operation = LOCK_EX;
        }

        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return flock($this->stream, $operation);
    }

    public function stream_truncate(int $new_size): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return ftruncate($this->stream, $new_size);
    }

    public function unlink(string $path): bool
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        return unlink($path, $this->context);
    }
}
