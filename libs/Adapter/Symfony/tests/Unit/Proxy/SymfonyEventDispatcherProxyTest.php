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

        // addListener is a Symfony-specific method forwarded via __call
        $called = false;
        $proxy->addListener('test.event', static function () use (&$called): void {
            $called = true;
        });

        $timeline->startup();
        $collector->startup();

        $proxy->dispatch(new \stdClass(), 'test.event');

        $this->assertTrue($called);
    }
}
