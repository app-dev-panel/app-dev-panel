<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\CacheListener;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class CacheListenerTest extends TestCase
{
    public function testRegistersFourEventListeners(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new CacheListener(fn() => $this->createCollector());
        $listener->register($dispatcher);

        $this->assertCount(4, $registeredListeners);
        $this->assertArrayHasKey(CacheHit::class, $registeredListeners);
        $this->assertArrayHasKey(CacheMissed::class, $registeredListeners);
        $this->assertArrayHasKey(KeyWritten::class, $registeredListeners);
        $this->assertArrayHasKey(KeyForgotten::class, $registeredListeners);
    }

    public function testRecordsCacheHit(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CacheHit('file', 'user:1', 'cached-value');
        $listeners[CacheHit::class]($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['operations']);
        $this->assertSame('get', $collected['operations'][0]['operation']);
        $this->assertSame('user:1', $collected['operations'][0]['key']);
        $this->assertTrue($collected['operations'][0]['hit']);
    }

    public function testRecordsCacheMiss(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CacheMissed('file', 'user:2');
        $listeners[CacheMissed::class]($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['operations']);
        $this->assertSame('get', $collected['operations'][0]['operation']);
        $this->assertSame('user:2', $collected['operations'][0]['key']);
        $this->assertFalse($collected['operations'][0]['hit']);
    }

    public function testRecordsCacheSet(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new KeyWritten('file', 'user:3', 'new-value', 3600);
        $listeners[KeyWritten::class]($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['operations']);
        $this->assertSame('set', $collected['operations'][0]['operation']);
        $this->assertSame('user:3', $collected['operations'][0]['key']);
    }

    public function testRecordsCacheDelete(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new KeyForgotten('file', 'user:4');
        $listeners[KeyForgotten::class]($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['operations']);
        $this->assertSame('delete', $collected['operations'][0]['operation']);
        $this->assertSame('user:4', $collected['operations'][0]['key']);
    }

    private function createCollector(): CacheCollector
    {
        $timeline = new TimelineCollector();
        $collector = new CacheCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    /**
     * @return array{CacheCollector, array<string, \Closure>}
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

        $listener = new CacheListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $listeners];
    }
}
