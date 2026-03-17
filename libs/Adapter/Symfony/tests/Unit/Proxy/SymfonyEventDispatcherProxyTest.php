<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SymfonyEventDispatcherProxyTest extends TestCase
{
    public function testDispatchCollectsEventAndDelegatesToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        // Start collectors (simulating debugger startup)
        $timeline->startup();
        $collector->startup();

        $event = new \stdClass();
        $result = $proxy->dispatch($event, 'test.event');

        $this->assertSame($event, $result);

        // Verify the event was collected
        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
    }

    public function testDispatchWithoutEventNameWorks(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $timeline->startup();
        $collector->startup();

        $event = new \stdClass();
        $result = $proxy->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testForwardsSymfonySpecificMethods(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $called = false;
        $proxy->addListener('test.event', static function () use (&$called): void {
            $called = true;
        });

        $timeline->startup();
        $collector->startup();

        $proxy->dispatch(new \stdClass(), 'test.event');

        $this->assertTrue($called);
    }

    public function testGetListenersForwardsToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $listener = static function (): void {};
        $proxy->addListener('my.event', $listener);

        $listeners = $proxy->getListeners('my.event');
        $this->assertCount(1, $listeners);

        $allListeners = $proxy->getListeners();
        $this->assertArrayHasKey('my.event', $allListeners);
    }

    public function testHasListenersForwardsToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $this->assertFalse($proxy->hasListeners('my.event'));

        $proxy->addListener('my.event', static function (): void {});

        $this->assertTrue($proxy->hasListeners('my.event'));
    }

    public function testRemoveListenerForwardsToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $listener = static function (): void {};
        $proxy->addListener('my.event', $listener);
        $this->assertTrue($proxy->hasListeners('my.event'));

        $proxy->removeListener('my.event', $listener);
        $this->assertFalse($proxy->hasListeners('my.event'));
    }

    public function testGetListenerPriorityForwardsToDecorated(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $listener = static function (): void {};
        $proxy->addListener('my.event', $listener, 42);

        $this->assertSame(42, $proxy->getListenerPriority('my.event', $listener));
    }

    public function testImplementsSymfonyComponentEventDispatcherInterface(): void
    {
        $timeline = new TimelineCollector();
        $collector = new EventCollector($timeline);
        $inner = new EventDispatcher();

        $proxy = new SymfonyEventDispatcherProxy($inner, $collector);

        $this->assertInstanceOf(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class, $proxy);
    }
}
