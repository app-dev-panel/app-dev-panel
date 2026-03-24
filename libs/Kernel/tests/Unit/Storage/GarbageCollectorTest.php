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
