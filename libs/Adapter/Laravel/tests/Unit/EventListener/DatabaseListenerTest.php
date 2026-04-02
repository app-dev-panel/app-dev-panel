<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\DatabaseListener;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\TestCase;

final class DatabaseListenerTest extends TestCase
{
    public function testRegistersQueryExecutedListener(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new DatabaseListener($this->createCollector(...));
        $listener->register($dispatcher);

        $this->assertArrayHasKey(QueryExecuted::class, $registeredListeners);
    }

    public function testRecordsQueryData(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);
        $event = new QueryExecuted('SELECT * FROM users WHERE id = ?', [42], 5.3, $connection);

        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $this->assertSame('SELECT * FROM users WHERE id = ?', $collected['queries'][0]['sql']);
        $this->assertSame('SELECT * FROM users WHERE id = 42', $collected['queries'][0]['rawSql']);
    }

    public function testSubstitutesStringBindings(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);
        $event = new QueryExecuted(
            'SELECT * FROM users WHERE name = ? AND status = ?',
            ['John', 'active'],
            2.1,
            $connection,
        );

        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $this->assertStringContainsString("'John'", $collected['queries'][0]['rawSql']);
        $this->assertStringContainsString("'active'", $collected['queries'][0]['rawSql']);
    }

    private function createCollector(): DatabaseCollector
    {
        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    public function testCollectFailedQuery(): void
    {
        $collector = $this->createCollector();

        $exception = new \Illuminate\Database\QueryException(
            'default',
            'INSERT INTO users (name) VALUES (?)',
            ['test-user'],
            new \RuntimeException('Duplicate entry'),
        );

        $listener = new DatabaseListener(static fn() => $collector);
        $listener->collectFailedQuery($exception);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $this->assertSame('INSERT INTO users (name) VALUES (?)', $collected['queries'][0]['sql']);
        $this->assertSame('error', $collected['queries'][0]['status']);
    }

    public function testRecordsQueryWithNoBindings(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);
        $event = new QueryExecuted('SELECT COUNT(*) FROM users', [], 1.0, $connection);

        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $this->assertSame('SELECT COUNT(*) FROM users', $collected['queries'][0]['sql']);
        $this->assertSame('SELECT COUNT(*) FROM users', $collected['queries'][0]['rawSql']);
    }

    public function testRecordsMultipleQueries(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);

        $callback(new QueryExecuted('SELECT 1', [], 0.1, $connection));
        $callback(new QueryExecuted('SELECT 2', [], 0.2, $connection));
        $callback(new QueryExecuted('SELECT 3', [], 0.3, $connection));

        $collected = $collector->getCollected();
        $this->assertCount(3, $collected['queries']);
    }

    public function testBuildRawSqlEscapesStringBindings(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);
        $event = new QueryExecuted(
            "SELECT * FROM users WHERE name = ? AND role = ?",
            ["O'Brien", 'admin'],
            1.0,
            $connection,
        );

        $callback($event);

        $collected = $collector->getCollected();
        $this->assertStringContainsString("O\\'Brien", $collected['queries'][0]['rawSql']);
        $this->assertStringContainsString("'admin'", $collected['queries'][0]['rawSql']);
    }

    public function testRecordsQueryTiming(): void
    {
        [$collector, $callback] = $this->registerListener();

        $connection = $this->createMock(Connection::class);
        $event = new QueryExecuted('SELECT 1', [], 10.5, $connection);

        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        // Query should have timing data in actions array
        $query = $collected['queries'][0];
        $this->assertArrayHasKey('actions', $query);
        $this->assertCount(2, $query['actions']); // query.start and query.end
        $this->assertSame('query.start', $query['actions'][0]['action']);
        $this->assertSame('query.end', $query['actions'][1]['action']);
        $this->assertGreaterThan(0, $query['actions'][1]['time'] - $query['actions'][0]['time']);
    }

    /**
     * @return array{DatabaseCollector, \Closure}
     */
    private function registerListener(): array
    {
        $collector = $this->createCollector();
        $callback = null;

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $cb) use (&$callback): void {
                $callback = $cb;
            });

        $listener = new DatabaseListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $callback];
    }
}
