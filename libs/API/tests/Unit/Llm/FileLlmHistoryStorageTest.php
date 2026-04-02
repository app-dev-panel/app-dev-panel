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
}
