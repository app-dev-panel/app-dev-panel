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

    public function testGetName(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewEntryTool($storage);

        $this->assertSame('view_debug_entry', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewEntryTool($storage);

        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('collector', strtolower($tool->getDescription()));
    }

    public function testGetInputSchema(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewEntryTool($storage);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('collector', $schema['properties']);
        $this->assertContains('id', $schema['required']);
    }

    public function testViewEntryWithCollectorFilterByFqcnSubstring(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'collector' => 'log']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('test message', $result['content'][0]['text']);
    }

    public function testViewEntryWithEmptyCollectorFilter(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'collector' => '']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('test message', $result['content'][0]['text']);
    }

    public function testViewEntryWithEmptyArrayData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [LogCollector::class => []],
            [],
        );
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('[]', $result['content'][0]['text']);
    }

    public function testViewEntryWithLargeDataIsTruncated(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $largeData = [];
        for ($i = 0; $i < 500; $i++) {
            $largeData[] = [
                'key' => str_repeat('x', 100),
                'value' => str_repeat('y', 100),
            ];
        }
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [LogCollector::class => $largeData],
            [],
        );
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('truncated', $text);
    }

    public function testViewEntryWithDeeplyNestedData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $nested = ['l1' => ['l2' => ['l3' => ['l4' => ['l5' => ['l6' => 'deep']]]]]];
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [LogCollector::class => $nested],
            [],
        );
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('entry-1', $result['content'][0]['text']);
    }

    public function testViewEntryWithScalarCollectorData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [LogCollector::class => 'scalar-value'],
            [],
        );
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('scalar-value', $result['content'][0]['text']);
    }

    public function testViewEntryInvalidCollectorShowsAvailable(): void
    {
        $storage = $this->createStorageWithEntry();
        $tool = new ViewEntryTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'collector' => 'nonexistent']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Available:', $result['content'][0]['text']);
        $this->assertStringContainsString('LogCollector', $result['content'][0]['text']);
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
