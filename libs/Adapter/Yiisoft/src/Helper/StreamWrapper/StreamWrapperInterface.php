<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Helper\StreamWrapper;

use const SEEK_SET;

/**
 * @link https://www.php.net/manual/en/class.streamwrapper.php
 */
interface StreamWrapperInterface
{
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool;

    public function stream_read(int $count): string|false;

    public function stream_set_option(int $option, int $arg1, int $arg2): bool;

    public function stream_tell(): int;

    public function stream_eof(): bool;

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * @return resource
     */
    public function stream_cast(int $castAs);

    public function stream_stat(): array|false;

    public function dir_closedir(): bool;

    public function dir_opendir(string $path, int $options): bool;

    public function dir_readdir(): false|string;

    public function dir_rewinddir(): bool;

    public function mkdir(string $path, int $mode, int $options): bool;

    public function rename(string $path_from, string $path_to): bool;

    public function rmdir(string $path, int $options): bool;

    public function stream_close(): void;

    public function stream_flush(): bool;

    public function stream_lock(int $operation): bool;

    public function stream_metadata(string $path, int $option, mixed $value): bool;

    public function stream_truncate(int $new_size): bool;

    public function stream_write(string $data): int;

    public function unlink(string $path): bool;

    public function url_stat(string $path, int $flags): array|false;
}
