<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\ViewEntryTool;
use PHPUnit\Framework\TestCase;

final class ViewEntryToolTest extends TestCase
{
    public function testViewEntryReturnsCollectorData(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('entry-1', $result['content'][0]['text']);
        $this->assertStringContainsString('test message', $result['content'][0]['text']);
    }

    public function testViewEntryWithCollectorFilter(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'collector' => 'LogCollector']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('test message', $result['content'][0]['text']);
    }

    public function testViewEntryWithInvalidCollectorFilter(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'collector' => 'nonexistent']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('not found', $result['content'][0]['text']);
    }

    public function testViewEntryNotFound(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'nonexistent']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('not found', $result['content'][0]['text']);
    }

    private function createStorageWithEntry(): MemoryStorage
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                LogCollector::class => [
                    [
                        'time' => 1000.0,
                        'level' => 'info',
                        'message' => 'test message',
                        'context' => [],
                        'line' => 'app.php:10',
                    ],
                ],
            ],
            [],
        );

        return $storage;
    }
}
