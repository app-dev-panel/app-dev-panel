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

    public function testGetName(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewDatabaseQueriesTool($storage);

        $this->assertSame('view_database_queries', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewDatabaseQueriesTool($storage);

        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('SQL', $tool->getDescription());
    }

    public function testGetInputSchema(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewDatabaseQueriesTool($storage);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('slow_only', $schema['properties']);
        $this->assertFalse($schema['properties']['slow_only']['default']);
    }

    public function testViewQueriesWithDuplicateGroups(): void
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
                            'line' => '',
                            'status' => 'success',
                            'rowsNumber' => 1,
                            'actions' => [
                                ['action' => 'query.start', 'time' => 1000.000],
                                ['action' => 'query.end', 'time' => 1000.005],
                            ],
                        ],
                    ],
                    'duplicates' => [
                        ['sql' => 'SELECT * FROM users WHERE id = ?', 'count' => 5],
                    ],
                ],
            ],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Duplicate groups', $text);
        $this->assertStringContainsString('N+1', $text);
    }

    public function testViewQueriesSlowOnlyWithNoSlowQueries(): void
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
                            'sql' => 'SELECT 1',
                            'params' => [],
                            'line' => '',
                            'status' => 'success',
                            'rowsNumber' => 1,
                            'actions' => [
                                ['action' => 'query.start', 'time' => 1000.000],
                                ['action' => 'query.end', 'time' => 1000.001],
                            ],
                        ],
                    ],
                ],
            ],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1', 'slow_only' => true]);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('No slow queries found', $text);
    }

    public function testViewQueriesWithQueryError(): void
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
                            'sql' => 'SELECT * FROM nonexistent_table',
                            'params' => [],
                            'line' => 'repo.php:10',
                            'status' => 'error',
                            'rowsNumber' => '?',
                            'actions' => [
                                ['action' => 'query.start', 'time' => 1000.000],
                                ['action' => 'query.error', 'time' => 1000.150],
                            ],
                        ],
                    ],
                ],
            ],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('SLOW', $text);
        $this->assertStringContainsString('error', $text);
        $this->assertStringContainsString('repo.php:10', $text);
    }

    public function testViewQueriesWithNoActions(): void
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
                            'sql' => 'SELECT 1',
                            'params' => [],
                            'line' => '',
                            'status' => 'success',
                            'rowsNumber' => 1,
                            'actions' => [],
                        ],
                    ],
                ],
            ],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('0.0ms', $text);
    }

    public function testViewQueriesAutoSelectsLatestWithNoQueryData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-no-queries', ['id' => 'entry-no-queries'], [], []);
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute([]);

        $this->assertStringContainsString('No database queries', $result['content'][0]['text']);
    }

    public function testViewQueriesWithEmptyId(): void
    {
        $storage = $this->createStorageWithQueries();
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => '']);

        $this->assertArrayNotHasKey('isError', $result);
    }

    public function testViewQueriesWithNonArrayData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [DatabaseCollector::class => ['queries' => 'not-an-array']],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertStringContainsString('No database queries', $result['content'][0]['text']);
    }

    public function testViewQueriesWithFlatArrayFormat(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                DatabaseCollector::class => [
                    [
                        'position' => 1,
                        'sql' => 'SELECT 1',
                        'params' => [],
                        'line' => '',
                        'status' => 'success',
                        'rowsNumber' => 1,
                        'actions' => [
                            ['action' => 'query.start', 'time' => 1000.000],
                            ['action' => 'query.end', 'time' => 1000.002],
                        ],
                    ],
                ],
            ],
            [],
        );
        $tool = new ViewDatabaseQueriesTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('SELECT 1', $text);
        $this->assertStringContainsString('1 queries', $text);
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
