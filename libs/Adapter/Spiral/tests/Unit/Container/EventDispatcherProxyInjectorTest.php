<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\EventDispatcherProxyInjector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionProperty;
use Spiral\Core\Container;

final class EventDispatcherProxyInjectorTest extends TestCase
{
    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new EventCollector(new TimelineCollector());

        $fake = new class implements EventDispatcherInterface {
            public ?object $dispatched = null;

            public function dispatch(object $event): object
            {
                $this->dispatched = $event;
                return $event;
            }
        };

        $container->bindSingleton(EventDispatcherInterface::class, $fake);

        $injector = new EventDispatcherProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(EventDispatcherProxyInjector::class, $injector);

        $binder->bindInjector(EventDispatcherInterface::class, EventDispatcherProxyInjector::class);

        $resolved = $container->get(EventDispatcherInterface::class);

        self::assertInstanceOf(EventDispatcherInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(EventDispatcherInterfaceProxy::class, 'decorated');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testFallsBackToDefaultWhenNothingBound(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new EventCollector(new TimelineCollector());

        $injector = new EventDispatcherProxyInjector($container, $binder, $collector);
        $container->bindSingleton(EventDispatcherProxyInjector::class, $injector);

        $binder->bindInjector(EventDispatcherInterface::class, EventDispatcherProxyInjector::class);

        $resolved = $container->get(EventDispatcherInterface::class);

        self::assertInstanceOf(EventDispatcherInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(EventDispatcherInterfaceProxy::class, 'decorated');
        $inner = $reflection->getValue($resolved);
        self::assertInstanceOf(EventDispatcherInterface::class, $inner);

        // The fallback is a no-op dispatcher: it simply returns the event unchanged.
        $event = new \stdClass();
        self::assertSame($event, $inner->dispatch($event));
    }

    public function testCollectorReceivesIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new EventCollector(new TimelineCollector());
        $collector->startup();

        $fake = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };

        $injector = new EventDispatcherProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(EventDispatcherProxyInjector::class, $injector);

        $binder->bindInjector(EventDispatcherInterface::class, EventDispatcherProxyInjector::class);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $event = new \stdClass();
        $dispatcher->dispatch($event);

        $entries = $collector->getCollected();
        self::assertCount(1, $entries);
        self::assertSame(\stdClass::class, $entries[0]['name']);
        self::assertSame($event, $entries[0]['event']);
    }
}
