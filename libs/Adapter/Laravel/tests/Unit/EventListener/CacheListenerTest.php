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

        $listener = new CacheListener($this->createCollector(...));
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

    public function testCacheHitWithNullStoreName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CacheHit(null, 'session:abc', 'data');
        $listeners[CacheHit::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('default', $collected['operations'][0]['pool']);
    }

    public function testCacheMissedWithNullStoreName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CacheMissed(null, 'session:xyz');
        $listeners[CacheMissed::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('default', $collected['operations'][0]['pool']);
    }

    public function testKeyWrittenWithNullStoreName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new KeyWritten(null, 'config:key', 'value', 60);
        $listeners[KeyWritten::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('default', $collected['operations'][0]['pool']);
    }

    public function testKeyForgottenWithNullStoreName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new KeyForgotten(null, 'temp:key');
        $listeners[KeyForgotten::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('default', $collected['operations'][0]['pool']);
    }

    public function testCacheHitWithCustomStoreName(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new CacheHit('redis', 'user:session', 'session-data');
        $listeners[CacheHit::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('redis', $collected['operations'][0]['pool']);
    }

    public function testKeyWrittenWithValue(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $event = new KeyWritten('redis', 'user:1:profile', ['name' => 'John'], 3600);
        $listeners[KeyWritten::class]($event);

        $collected = $collector->getCollected();
        $this->assertSame('set', $collected['operations'][0]['operation']);
        $this->assertSame('user:1:profile', $collected['operations'][0]['key']);
        $this->assertSame('redis', $collected['operations'][0]['pool']);
    }

    public function testMultipleEventsRecordedSequentially(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $listeners[CacheMissed::class](new CacheMissed('file', 'key1'));
        $listeners[KeyWritten::class](new KeyWritten('file', 'key1', 'value', 300));
        $listeners[CacheHit::class](new CacheHit('file', 'key1', 'value'));

        $collected = $collector->getCollected();
        $this->assertCount(3, $collected['operations']);
        $this->assertSame('get', $collected['operations'][0]['operation']);
        $this->assertFalse($collected['operations'][0]['hit']);
        $this->assertSame('set', $collected['operations'][1]['operation']);
        $this->assertSame('get', $collected['operations'][2]['operation']);
        $this->assertTrue($collected['operations'][2]['hit']);
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
