<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;

final class EventDispatcherInterfaceProxyTest extends TestCase
{
    public function testDispatch(): void
    {
        $event = new stdClass();
        $collector = new EventCollector(new TimelineCollector());
        $collector->startup();

        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects($this->once())->method('dispatch')->with($event)->willReturn($event);
        $eventDispatcher = new EventDispatcherInterfaceProxy($eventDispatcherMock, $collector);

        $newEvent = $eventDispatcher->dispatch($event);

        $this->assertSame($event, $newEvent);
        $this->assertCount(1, $collector->getCollected());
    }

    public function testProxyDecoratedCall(): void
    {
        $dispatcher = new class() implements EventDispatcherInterface {
            public $var = null;

            public function getProxiedCall(): string
            {
                return 'ok';
            }

            public function setProxiedCall($args): mixed
            {
                return $args;
            }

            public function dispatch(object $event) {}
        };
        $collector = new EventCollector(new TimelineCollector());
        $proxy = new EventDispatcherInterfaceProxy($dispatcher, $collector);

        $this->assertEquals('ok', $proxy->getProxiedCall());
        $this->assertEquals($args = [1, new stdClass(), 'string'], $proxy->setProxiedCall($args));
        $proxy->var = '123';
        $this->assertEquals('123', $proxy->var);
    }
}
