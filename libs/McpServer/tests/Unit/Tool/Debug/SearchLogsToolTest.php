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
