<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\Storage\FileStorageGarbageCollector;
use PHPUnit\Framework\TestCase;

final class FileStorageGarbageCollectorTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-fsgc-test-' . uniqid();
        mkdir($this->storagePath, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testRunWithNoEntries(): void
    {
        $gc = new FileStorageGarbageCollector($this->storagePath, 50);
        $gc->run();

        $this->assertDirectoryExists($this->storagePath);
    }

    public function testRunKeepsEntriesWithinLimit(): void
    {
        $this->createEntry('aa', '01');
        $this->createEntry('aa', '02');

        $gc = new FileStorageGarbageCollector($this->storagePath, 10);
        $gc->run();

        $this->assertFileExists($this->storagePath . '/aa/01/summary.json');
        $this->assertFileExists($this->storagePath . '/aa/02/summary.json');
    }

    public function testRunRemovesOldestEntries(): void
    {
        $this->createEntry('aa', 'old', time() - 200);
        $this->createEntry('bb', 'mid', time() - 100);
        $this->createEntry('cc', 'new', time());

        $gc = new FileStorageGarbageCollector($this->storagePath, 2);
        $gc->run();

        $this->assertFileExists($this->storagePath . '/cc/new/summary.json');
        $this->assertFileExists($this->storagePath . '/bb/mid/summary.json');
        $this->assertFileDoesNotExist($this->storagePath . '/aa/old/summary.json');
    }

    public function testRunCleansEmptyGroupDirectories(): void
    {
        $this->createEntry('aa', 'old', time() - 100);
        $this->createEntry('bb', 'new', time());

        $gc = new FileStorageGarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertDirectoryDoesNotExist($this->storagePath . '/aa');
        $this->assertDirectoryExists($this->storagePath . '/bb');
    }

    public function testRunCleansUpLockFile(): void
    {
        $this->createEntry('aa', '01');

        $gc = new FileStorageGarbageCollector($this->storagePath, 50);
        $gc->run();

        $this->assertFileDoesNotExist($this->storagePath . '/.gc.lock');
    }

    public function testRunWithGzipSummaries(): void
    {
        $dir1 = $this->storagePath . '/aa/gz-old';
        mkdir($dir1, 0o777, true);
        $file1 = $dir1 . '/summary.json.gz';
        file_put_contents($file1, gzencode(json_encode(['id' => 'gz-old'])));
        touch($file1, time() - 200);

        $dir2 = $this->storagePath . '/bb/gz-new';
        mkdir($dir2, 0o777, true);
        $file2 = $dir2 . '/summary.json.gz';
        file_put_contents($file2, gzencode(json_encode(['id' => 'gz-new'])));

        $gc = new FileStorageGarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertFileDoesNotExist($file1);
        $this->assertFileExists($file2);
    }

    public function testRunWithExactHistorySize(): void
    {
        $this->createEntry('aa', '01', time() - 10);
        $this->createEntry('bb', '02', time());

        $gc = new FileStorageGarbageCollector($this->storagePath, 2);
        $gc->run();

        $this->assertFileExists($this->storagePath . '/aa/01/summary.json');
        $this->assertFileExists($this->storagePath . '/bb/02/summary.json');
    }

    private function createEntry(string $group, string $id, ?int $mtime = null): void
    {
        $dir = $this->storagePath . '/' . $group . '/' . $id;
        mkdir($dir, 0o777, true);
        $file = $dir . '/summary.json';
        file_put_contents($file, json_encode(['id' => $id]));
        if ($mtime !== null) {
            touch($file, $mtime);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}
