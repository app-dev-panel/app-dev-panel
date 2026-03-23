<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use Yiisoft\Files\FileHelper;

use function array_slice;
use function count;
use function dirname;
use function filemtime;
use function glob;
use function strlen;
use function substr;
use function uasort;

final class FileStorageGarbageCollector
{
    public function __construct(
        private readonly string $path,
        private readonly int $historySize,
    ) {}

    public function run(): void
    {
        $lockHandle = $this->acquireLock();
        if ($lockHandle === false) {
            return;
        }

        try {
            $this->removeExcessEntries();
        } finally {
            $this->releaseLock($lockHandle);
        }
    }

    /**
     * @return resource|false Lock file handle, or false if lock could not be acquired
     */
    private function acquireLock(): mixed
    {
        $lockFile = $this->path . '/.gc.lock';
        set_error_handler(static fn(): bool => true);
        try {
            $lockHandle = fopen($lockFile, 'c');
        } finally {
            restore_error_handler();
        }
        if ($lockHandle === false) {
            return false;
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return false;
        }

        return $lockHandle;
    }

    private function removeExcessEntries(): void
    {
        $summaryFiles = glob($this->path . '/**/**/summary.json', GLOB_NOSORT);
        if ($summaryFiles === false || $summaryFiles === [] || count($summaryFiles) <= $this->historySize) {
            return;
        }

        uasort($summaryFiles, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $excessFiles = array_slice($summaryFiles, $this->historySize);
        foreach ($excessFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $path1 = dirname($file);
            $path2 = dirname($file, 2);
            $path3 = dirname($file, 3);
            $resource = substr($path1, strlen($path3));

            FileHelper::removeDirectory($this->path . $resource);

            // Clean empty group directories
            $group = substr($path2, strlen($path3));
            if (FileHelper::isEmptyDirectory($this->path . $group)) {
                FileHelper::removeDirectory($this->path . $group);
            }
        }
    }

    /**
     * @param resource $lockHandle
     */
    private function releaseLock(mixed $lockHandle): void
    {
        $lockFile = $this->path . '/.gc.lock';
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}
