<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\ViewDatabaseQueriesTool;
use PHPUnit\Framework\TestCase;

final class ViewDatabaseQueriesToolTest extends TestCase
{
    public function testViewQueriesShowsAllQueries(): void
    {
        $storage = $this->createStorageWithQueries();
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('SELECT', $text);
        $this->assertStringContainsString('2 queries', $text);
    }

    public function testViewQueriesSlowOnly(): void
    {
        $storage = $this->createStorageWithQueries();
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'slow_only' => true]);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('SLOW', $text);
        $this->assertStringContainsString('SELECT * FROM orders', $text);
    }

    public function testViewQueriesAutoSelectsLatestEntry(): void
    {
        $storage = $this->createStorageWithQueries();
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
    }

    public function testViewQueriesEntryNotFound(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'nonexistent']);

        $this->assertTrue($result['isError']);
    }

    public function testViewQueriesNoQueries(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-1', ['id' => 'entry-1'], [], []);
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertStringContainsString('No database queries', $result['content'][0]['text']);
    }

    private function createStorageWithQueries(): MemoryStorage
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                DatabaseCollector::class => [
                    'queries' => [
                        [
                            'position' => 1,
                            'sql' => 'SELECT * FROM users WHERE id = ?',
                            'rawSql' => 'SELECT * FROM users WHERE id = 1',
                            'params' => [1],
                            'line' => 'UserRepository.php:30',
                            'status' => 'success',
                            'rowsNumber' => 1,
                            'actions' => [
                                ['action' => 'query.start', 'time' => 1000.000],
                                ['action' => 'query.end', 'time' => 1000.005],
                            ],
                        ],
                        [
                            'position' => 2,
                            'sql' => 'SELECT * FROM orders',
                            'rawSql' => 'SELECT * FROM orders',
                            'params' => [],
                            'line' => 'OrderRepository.php:15',
                            'status' => 'success',
                            'rowsNumber' => 50000,
                            'actions' => [
                                ['action' => 'query.start', 'time' => 1000.010],
                                ['action' => 'query.end', 'time' => 1000.210],
                            ],
                        ],
                    ],
                ],
            ],
            [],
        );

        return $storage;
    }
}
