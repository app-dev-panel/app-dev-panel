<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\Storage\GarbageCollector;
use PHPUnit\Framework\TestCase;

final class GarbageCollectorTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-gc-test-' . uniqid();
        mkdir($this->storagePath, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testRunWithNoEntries(): void
    {
        $gc = new GarbageCollector($this->storagePath, 50);
        $gc->run();

        $this->assertDirectoryExists($this->storagePath);
    }

    public function testRunKeepsEntriesWithinLimit(): void
    {
        $this->createEntry('aa', '01');
        $this->createEntry('aa', '02');
        $this->createEntry('bb', '03');

        $gc = new GarbageCollector($this->storagePath, 5);
        $gc->run();

        $this->assertFileExists($this->storagePath . '/aa/01/summary.json');
        $this->assertFileExists($this->storagePath . '/aa/02/summary.json');
        $this->assertFileExists($this->storagePath . '/bb/03/summary.json');
    }

    public function testRunRemovesExcessEntries(): void
    {
        $this->createEntry('aa', '01', time() - 100);
        $this->createEntry('aa', '02', time() - 50);
        $this->createEntry('bb', '03', time());

        $gc = new GarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertFileExists($this->storagePath . '/bb/03/summary.json');
        $this->assertFileDoesNotExist($this->storagePath . '/aa/01/summary.json');
        $this->assertFileDoesNotExist($this->storagePath . '/aa/02/summary.json');
    }

    public function testRunCleansEmptyGroupDirectories(): void
    {
        $this->createEntry('aa', '01', time() - 100);
        $this->createEntry('bb', '02', time());

        $gc = new GarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertDirectoryDoesNotExist($this->storagePath . '/aa');
        $this->assertDirectoryExists($this->storagePath . '/bb');
    }

    public function testRunCleansUpLockFile(): void
    {
        $this->createEntry('aa', '01');

        $gc = new GarbageCollector($this->storagePath, 50);
        $gc->run();

        $this->assertFileDoesNotExist($this->storagePath . '/.gc.lock');
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

    public function testRunWithGzipFiles(): void
    {
        $dir = $this->storagePath . '/aa/01';
        mkdir($dir, 0o777, true);
        $file = $dir . '/summary.json.gz';
        file_put_contents($file, gzencode(json_encode(['id' => '01'])));

        $gc = new GarbageCollector($this->storagePath, 50);
        $gc->run();

        $this->assertFileExists($file);
    }

    public function testRunRemovesExcessGzipEntries(): void
    {
        // Create old gzip entry
        $dir1 = $this->storagePath . '/aa/old';
        mkdir($dir1, 0o777, true);
        $file1 = $dir1 . '/summary.json.gz';
        file_put_contents($file1, gzencode(json_encode(['id' => 'old'])));
        touch($file1, time() - 100);

        // Create new entry
        $dir2 = $this->storagePath . '/bb/new';
        mkdir($dir2, 0o777, true);
        $file2 = $dir2 . '/summary.json.gz';
        file_put_contents($file2, gzencode(json_encode(['id' => 'new'])));

        $gc = new GarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertFileDoesNotExist($file1);
        $this->assertFileExists($file2);
    }

    public function testRunWithMixedGzipAndJsonEntries(): void
    {
        // Create old json entry
        $this->createEntry('aa', 'old-json', time() - 200);

        // Create new gzip entry
        $dir = $this->storagePath . '/bb/new-gz';
        mkdir($dir, 0o777, true);
        $file = $dir . '/summary.json.gz';
        file_put_contents($file, gzencode(json_encode(['id' => 'new-gz'])));

        $gc = new GarbageCollector($this->storagePath, 1);
        $gc->run();

        $this->assertFileExists($file);
        $this->assertFileDoesNotExist($this->storagePath . '/aa/old-json/summary.json');
    }

    public function testRunWithExactlyHistorySizeEntries(): void
    {
        $this->createEntry('aa', '01');
        $this->createEntry('bb', '02');

        $gc = new GarbageCollector($this->storagePath, 2);
        $gc->run();

        // Both should be preserved (count <= historySize)
        $this->assertFileExists($this->storagePath . '/aa/01/summary.json');
        $this->assertFileExists($this->storagePath . '/bb/02/summary.json');
    }

    public function testRunSkipsWhenLockAlreadyHeld(): void
    {
        $this->createEntry('aa', '01', time() - 100);
        $this->createEntry('bb', '02', time());

        // Acquire the lock externally to simulate concurrent GC
        $lockFile = $this->storagePath . '/.gc.lock';
        $lockHandle = fopen($lockFile, 'c');
        flock($lockHandle, LOCK_EX | LOCK_NB);

        try {
            $gc = new GarbageCollector($this->storagePath, 1);
            $gc->run();

            // Both entries should still exist since GC couldn't acquire lock
            $this->assertFileExists($this->storagePath . '/aa/01/summary.json');
            $this->assertFileExists($this->storagePath . '/bb/02/summary.json');
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }

    public function testRunWithNonExistentPath(): void
    {
        $gc = new GarbageCollector($this->storagePath . '/nonexistent', 50);
        // Should not throw - acquireGcLock fails, run returns early
        $gc->run();
        $this->assertTrue(true);
    }

    public function testRunRemovesMultipleExcessGroups(): void
    {
        $this->createEntry('aa', '01', time() - 300);
        $this->createEntry('bb', '02', time() - 200);
        $this->createEntry('cc', '03', time() - 100);
        $this->createEntry('dd', '04', time());

        $gc = new GarbageCollector($this->storagePath, 2);
        $gc->run();

        // Two newest should survive
        $this->assertFileExists($this->storagePath . '/dd/04/summary.json');
        $this->assertFileExists($this->storagePath . '/cc/03/summary.json');
        // Two oldest should be removed
        $this->assertFileDoesNotExist($this->storagePath . '/aa/01/summary.json');
        $this->assertFileDoesNotExist($this->storagePath . '/bb/02/summary.json');
        // Empty group directories should be cleaned
        $this->assertDirectoryDoesNotExist($this->storagePath . '/aa');
        $this->assertDirectoryDoesNotExist($this->storagePath . '/bb');
    }

    public function testRunKeepsGroupWithRemainingEntries(): void
    {
        $this->createEntry('aa', '01', time() - 200);
        $this->createEntry('aa', '02', time());

        $gc = new GarbageCollector($this->storagePath, 1);
        $gc->run();

        // Group 'aa' should still exist because entry '02' is kept
        $this->assertDirectoryExists($this->storagePath . '/aa');
        $this->assertFileExists($this->storagePath . '/aa/02/summary.json');
        $this->assertFileDoesNotExist($this->storagePath . '/aa/01/summary.json');
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
