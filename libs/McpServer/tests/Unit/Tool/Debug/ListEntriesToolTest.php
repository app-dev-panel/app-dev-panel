<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\ListEntriesTool;
use PHPUnit\Framework\TestCase;

final class ListEntriesToolTest extends TestCase
{
    public function testListEntriesReturnsFormattedEntries(): void
    {
        $storage = $this->createStorageWithEntries();
        $tool = new ListEntriesTool($storage);

        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('entry-1', $result['content'][0]['text']);
        $this->assertStringContainsString('entry-2', $result['content'][0]['text']);
    }

    public function testListEntriesWithLimit(): void
    {
        $storage = $this->createStorageWithEntries();
        $tool = new ListEntriesTool($storage);

        $result = $tool->execute(['limit' => 1]);

        $text = $result['content'][0]['text'];
        // Should only have one entry (the latest — entry-2 since reversed)
        $this->assertSame(1, substr_count($text, '- **'));
    }

    public function testListEntriesWithFilter(): void
    {
        $storage = $this->createStorageWithEntries();
        $tool = new ListEntriesTool($storage);

        $result = $tool->execute(['filter' => '/api/users']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('entry-1', $text);
        $this->assertStringNotContainsString('entry-2', $text);
    }

    public function testListEntriesWithNoWrittenEntries(): void
    {
        // MemoryStorage always has a "current session" entry from the DebuggerIdGenerator,
        // so with no explicit writes, we still get one entry
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ListEntriesTool($storage);

        $result = $tool->execute(['filter' => 'nonexistent-filter-xyz']);

        $this->assertStringContainsString('No debug entries', $result['content'][0]['text']);
    }

    public function testNameAndDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ListEntriesTool($storage);

        $this->assertSame('list_debug_entries', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());
        $this->assertSame('object', $tool->getInputSchema()['type']);
    }

    private function createStorageWithEntries(): MemoryStorage
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $storage->write(
            'entry-1',
            ['id' => 'entry-1', 'request' => ['method' => 'GET', 'url' => '/api/users', 'statusCode' => '200']],
            [],
            [],
        );

        $storage->write(
            'entry-2',
            ['id' => 'entry-2', 'request' => ['method' => 'POST', 'url' => '/api/orders', 'statusCode' => '500']],
            [],
            [],
        );

        return $storage;
    }
}
