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
