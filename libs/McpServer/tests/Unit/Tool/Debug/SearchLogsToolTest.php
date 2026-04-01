<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\SearchLogsTool;
use PHPUnit\Framework\TestCase;

final class SearchLogsToolTest extends TestCase
{
    public function testSearchFindsMatchingLogs(): void
    {
        $storage = $this->createStorageWithLogs();
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'database']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('Database connection', $result['content'][0]['text']);
        $this->assertStringContainsString('Found 1', $result['content'][0]['text']);
    }

    public function testSearchFiltersByLevel(): void
    {
        $storage = $this->createStorageWithLogs();
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'message', 'level' => 'error']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Database connection', $text);
        $this->assertStringNotContainsString('User logged in', $text);
    }

    public function testSearchWithNoResults(): void
    {
        $storage = $this->createStorageWithLogs();
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'nonexistent-xyz']);

        $this->assertStringContainsString('No log entries', $result['content'][0]['text']);
    }

    public function testSearchEmptyQueryReturnsError(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => '']);

        $this->assertTrue($result['isError']);
    }

    public function testSearchRespectsLimit(): void
    {
        $storage = $this->createStorageWithLogs();
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'message', 'limit' => 1]);

        $this->assertStringContainsString('Found 1', $result['content'][0]['text']);
    }

    public function testGetName(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new SearchLogsTool($storage);

        $this->assertSame('search_logs', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new SearchLogsTool($storage);

        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('log', strtolower($tool->getDescription()));
    }

    public function testGetInputSchema(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new SearchLogsTool($storage);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertArrayHasKey('level', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertContains('query', $schema['required']);
    }

    public function testSearchMatchesContext(): void
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
                        'message' => 'Something happened',
                        'context' => ['user_id' => 42, 'action' => 'special_context_value'],
                        'line' => 'app.php:10',
                    ],
                ],
            ],
            [],
        );
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'special_context_value']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('Found 1', $result['content'][0]['text']);
        $this->assertStringContainsString('Something happened', $result['content'][0]['text']);
    }

    public function testSearchAcrossMultipleEntries(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                LogCollector::class => [
                    ['time' => 1000.0, 'level' => 'info', 'message' => 'Target message in entry 1', 'context' => [], 'line' => ''],
                ],
            ],
            [],
        );
        $storage->write(
            'entry-2',
            ['id' => 'entry-2'],
            [
                LogCollector::class => [
                    ['time' => 1001.0, 'level' => 'error', 'message' => 'Target message in entry 2', 'context' => [], 'line' => ''],
                ],
            ],
            [],
        );
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'target message']);

        $this->assertStringContainsString('Found 2', $result['content'][0]['text']);
    }

    public function testSearchSkipsEntriesWithoutLogCollector(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-1', ['id' => 'entry-1'], [], []);
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'anything']);

        $this->assertStringContainsString('No log entries', $result['content'][0]['text']);
    }

    public function testSearchSkipsNonArrayLogData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [LogCollector::class => 'not-an-array'],
            [],
        );
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'anything']);

        $this->assertStringContainsString('No log entries', $result['content'][0]['text']);
    }

    public function testSearchWithMissingQueryKey(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute([]);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('empty', $result['content'][0]['text']);
    }

    public function testFormatLogWithEmptyLine(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                LogCollector::class => [
                    ['time' => 1000.0, 'level' => 'warning', 'message' => 'No line info', 'context' => [], 'line' => ''],
                ],
            ],
            [],
        );
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'no line']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('WARNING', $text);
        $this->assertStringNotContainsString(' @ ', $text);
    }

    public function testFormatLogWithMissingLevel(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                LogCollector::class => [
                    ['time' => 1000.0, 'message' => 'Missing level field', 'context' => [], 'line' => ''],
                ],
            ],
            [],
        );
        $tool = new SearchLogsTool($storage);

        $result = $tool->execute(['query' => 'missing level']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('UNKNOWN', $text);
    }

    private function createStorageWithLogs(): MemoryStorage
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
                        'message' => 'User logged in — info message',
                        'context' => [],
                        'line' => 'auth.php:50',
                    ],
                    [
                        'time' => 1001.0,
                        'level' => 'error',
                        'message' => 'Database connection failed — error message',
                        'context' => ['host' => 'db'],
                        'line' => 'db.php:20',
                    ],
                ],
            ],
            [],
        );

        return $storage;
    }
}
