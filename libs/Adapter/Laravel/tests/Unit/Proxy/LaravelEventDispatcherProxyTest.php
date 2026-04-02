<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class LaravelEventDispatcherProxyTest extends TestCase
{
    private TimelineCollector $timeline;
    private EventCollector $collector;

    protected function setUp(): void
    {
        $this->timeline = new TimelineCollector();
        $this->collector = new EventCollector($this->timeline);
        $this->timeline->startup();
        $this->collector->startup();
    }

    public function testDispatchForwardsToDecoratedAndCollectsObjectEvents(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isType('object'), [], false)
            ->willReturn(['result']);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);

        $event = new \stdClass();
        $result = $proxy->dispatch($event);

        $this->assertSame(['result'], $result);
        $collected = $this->collector->getCollected();
        $this->assertCount(1, $collected);
    }

    public function testDispatchDoesNotCollectStringEvents(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('dispatch')->willReturn(null);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->dispatch('string.event');

        $collected = $this->collector->getCollected();
        $this->assertCount(0, $collected);
    }

    public function testListenForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $callback = static fn() => null;
        $inner->expects($this->once())->method('listen')->with('test.event', $callback);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->listen('test.event', $callback);
    }

    public function testHasListenersForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('hasListeners')->with('test.event')->willReturn(true);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $this->assertTrue($proxy->hasListeners('test.event'));
    }

    public function testSubscribeForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $subscriber = new \stdClass();
        $inner->expects($this->once())->method('subscribe')->with($subscriber);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->subscribe($subscriber);
    }

    public function testUntilForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('until')->with('test.event', [])->willReturn('stopped');

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $this->assertSame('stopped', $proxy->until('test.event'));
    }

    public function testPushForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('push')->with('test.event', []);

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->push('test.event');
    }

    public function testFlushForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('flush')->with('test.event');

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->flush('test.event');
    }

    public function testForgetForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('forget')->with('test.event');

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->forget('test.event');
    }

    public function testForgetPushedForwardsToDecorated(): void
    {
        $inner = $this->createMock(Dispatcher::class);
        $inner->expects($this->once())->method('forgetPushed');

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);
        $proxy->forgetPushed();
    }

    public function testMagicCallForwardsToDecorated(): void
    {
        // Create a concrete dispatcher that has extra methods beyond the interface
        $inner = new class implements Dispatcher {
            public bool $customMethodCalled = false;
            public string $customMethodArg = '';

            public function listen($events, $listener = null): void {}

            public function hasListeners($eventName): bool
            {
                return false;
            }

            public function subscribe($subscriber): void {}

            public function until($event, $payload = []): mixed
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false): mixed
            {
                return null;
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}

            public function customMethod(string $arg): string
            {
                $this->customMethodCalled = true;
                $this->customMethodArg = $arg;
                return 'custom-result';
            }
        };

        $proxy = new LaravelEventDispatcherProxy($inner, $this->collector);

        /** @var string $result */
        $result = $proxy->customMethod('test-arg');

        $this->assertSame('custom-result', $result);
        $this->assertTrue($inner->customMethodCalled);
        $this->assertSame('test-arg', $inner->customMethodArg);
    }
}
