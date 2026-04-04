<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\RedisListener;
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Events\CommandExecuted;
use PHPUnit\Framework\TestCase;

final class RedisListenerTest extends TestCase
{
    public function testRegistersOneEventListener(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new RedisListener($this->createCollector(...));
        $listener->register($dispatcher);

        $this->assertCount(1, $registeredListeners);
        $this->assertArrayHasKey(CommandExecuted::class, $registeredListeners);
    }

    public function testRecordsRedisCommand(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CommandExecuted('set', ['user:42', 'value'], 1.5, $this->createConnection('default'));
        $listeners[CommandExecuted::class]($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['commands']);
        $this->assertSame('SET', $collected['commands'][0]['command']);
        $this->assertSame('default', $collected['commands'][0]['connection']);
        $this->assertSame(['user:42', 'value'], $collected['commands'][0]['arguments']);
    }

    public function testCommandNameIsUppercased(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CommandExecuted('get', ['key'], 0.5, $this->createConnection('default'));
        $listeners[CommandExecuted::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('GET', $collected['commands'][0]['command']);
    }

    public function testDurationIsConvertedFromMillisecondsToSeconds(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CommandExecuted('ping', [], 2.0, $this->createConnection('default'));
        $listeners[CommandExecuted::class]($event);

        $collected = $collector->getCollected();
        $this->assertEqualsWithDelta(0.002, $collected['commands'][0]['duration'], 0.0001);
    }

    public function testRecordsConnectionName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CommandExecuted('lpush', ['queue:jobs', 'payload'], 0.8, $this->createConnection('cache'));
        $listeners[CommandExecuted::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('cache', $collected['commands'][0]['connection']);
    }

    public function testMultipleCommandsRecordedSequentially(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $conn = $this->createConnection('default');
        $listeners[CommandExecuted::class](new CommandExecuted('set', ['k1', 'v1'], 1.0, $conn));
        $listeners[CommandExecuted::class](new CommandExecuted('get', ['k1'], 0.5, $conn));
        $listeners[CommandExecuted::class](new CommandExecuted('del', ['k1'], 0.3, $conn));

        $collected = $collector->getCollected();
        $this->assertCount(3, $collected['commands']);
        $this->assertSame('SET', $collected['commands'][0]['command']);
        $this->assertSame('GET', $collected['commands'][1]['command']);
        $this->assertSame('DEL', $collected['commands'][2]['command']);
    }

    private function createCollector(): RedisCollector
    {
        $timeline = new TimelineCollector();
        $collector = new RedisCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    private function createConnection(string $name): \Illuminate\Redis\Connections\Connection
    {
        $connection = $this->createMock(\Illuminate\Redis\Connections\Connection::class);
        $connection->method('getName')->willReturn($name);
        return $connection;
    }

    /**
     * @return array{RedisCollector, array<string, \Closure>}
     */
    private function registerListener(): array
    {
        $collector = $this->createCollector();
        $listeners = [];

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$listeners): void {
                $listeners[$event] = $callback;
            });

        $listener = new RedisListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $listeners];
    }
}
