<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm;

use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use PHPUnit\Framework\TestCase;

final class FileLlmHistoryStorageTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/adp-llm-history-' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/.llm-history.json');
        @rmdir($this->tmpDir);
    }

    public function testDefaultsEmpty(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);

        $this->assertSame([], $storage->getAll());
    }

    public function testAdd(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'Why error?', 'response' => 'Bug found', 'timestamp' => 1000]);

        $all = $storage->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('Why error?', $all[0]['query']);
    }

    public function testAddPrependsNewest(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'first', 'response' => 'r1', 'timestamp' => 1000]);
        $storage->add(['query' => 'second', 'response' => 'r2', 'timestamp' => 2000]);

        $all = $storage->getAll();
        $this->assertSame('second', $all[0]['query']);
        $this->assertSame('first', $all[1]['query']);
    }

    public function testDelete(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);
        $storage->add(['query' => 'b', 'response' => 'r', 'timestamp' => 2]);

        $storage->delete(0);

        $all = $storage->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('a', $all[0]['query']);
    }

    public function testDeleteInvalidIndex(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);

        $storage->delete(999);

        $this->assertCount(1, $storage->getAll());
    }

    public function testClear(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);
        $storage->add(['query' => 'b', 'response' => 'r', 'timestamp' => 2]);

        $storage->clear();

        $this->assertSame([], $storage->getAll());
    }

    public function testClearDeletesFile(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);
        $storage->clear();

        $this->assertFileDoesNotExist($this->tmpDir . '/.llm-history.json');
    }

    public function testPersistenceToDisk(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'persistent', 'response' => 'yes', 'timestamp' => 1]);

        $storage2 = new FileLlmHistoryStorage($this->tmpDir);
        $all = $storage2->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('persistent', $all[0]['query']);
    }

    public function testMaxEntries(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        for ($i = 0; $i < 105; $i++) {
            $storage->add(['query' => "q$i", 'response' => "r$i", 'timestamp' => $i]);
        }

        $all = $storage->getAll();
        $this->assertCount(100, $all);
        // Most recent should be first
        $this->assertSame('q104', $all[0]['query']);
    }

    public function testClearThenReuse(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'before', 'response' => 'r', 'timestamp' => 1]);
        $storage->clear();
        $storage->add(['query' => 'after', 'response' => 'r', 'timestamp' => 2]);

        $all = $storage->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('after', $all[0]['query']);
    }

    public function testDeleteLastIndex(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'only', 'response' => 'r', 'timestamp' => 1]);
        $storage->delete(0);

        $this->assertSame([], $storage->getAll());
    }

    public function testClearOnEmptyStorageDoesNotFail(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->clear();

        $this->assertSame([], $storage->getAll());
    }

    public function testDeleteMiddleElement(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'c', 'response' => 'r', 'timestamp' => 3]);
        $storage->add(['query' => 'b', 'response' => 'r', 'timestamp' => 2]);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);

        // Items are prepended, so order is [a, b, c]
        $storage->delete(1);

        $all = $storage->getAll();
        $this->assertCount(2, $all);
        $this->assertSame('a', $all[0]['query']);
        $this->assertSame('c', $all[1]['query']);
    }

    public function testAddWithErrorField(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'q', 'response' => '', 'timestamp' => 1, 'error' => 'Timeout']);

        $all = $storage->getAll();
        $this->assertSame('Timeout', $all[0]['error']);
    }

    public function testStorageCreatesDirectoryIfNeeded(): void
    {
        $nested = $this->tmpDir . '/nested/dir';
        $storage = new FileLlmHistoryStorage($nested);
        $storage->add(['query' => 'q', 'response' => 'r', 'timestamp' => 1]);

        $this->assertFileExists($nested . '/.llm-history.json');

        // Cleanup
        @unlink($nested . '/.llm-history.json');
        @rmdir($nested);
        @rmdir($this->tmpDir . '/nested');
    }

    public function testDeleteNegativeIndex(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r', 'timestamp' => 1]);

        $storage->delete(-1);

        $this->assertCount(1, $storage->getAll());
    }

    public function testLoadFromNonArrayJsonReturnsEmpty(): void
    {
        file_put_contents($this->tmpDir . '/.llm-history.json', '"just a string"');

        $storage = new FileLlmHistoryStorage($this->tmpDir);

        $this->assertSame([], $storage->getAll());
    }

    public function testDeletePersistsChange(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'a', 'response' => 'r1', 'timestamp' => 1]);
        $storage->add(['query' => 'b', 'response' => 'r2', 'timestamp' => 2]);
        $storage->delete(0);

        $storage2 = new FileLlmHistoryStorage($this->tmpDir);
        $all = $storage2->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('a', $all[0]['query']);
    }

    public function testClearDeletesFileAndNewInstanceIsEmpty(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'x', 'response' => 'y', 'timestamp' => 1]);
        $storage->clear();

        $storage2 = new FileLlmHistoryStorage($this->tmpDir);
        $this->assertSame([], $storage2->getAll());
    }

    public function testAddAfterClearOnSameInstance(): void
    {
        $storage = new FileLlmHistoryStorage($this->tmpDir);
        $storage->add(['query' => 'before', 'response' => 'r', 'timestamp' => 1]);
        $storage->clear();
        $storage->add(['query' => 'after', 'response' => 'r', 'timestamp' => 2]);

        $all = $storage->getAll();
        $this->assertCount(1, $all);
        $this->assertSame('after', $all[0]['query']);
    }
}
