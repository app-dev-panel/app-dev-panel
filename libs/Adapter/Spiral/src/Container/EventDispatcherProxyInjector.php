<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;

/**
 * Spiral container injector that wraps any `Psr\EventDispatcher\EventDispatcherInterface`
 * resolution with {@see EventDispatcherInterfaceProxy} so dispatched events are forwarded
 * to {@see EventCollector}.
 *
 * If the application has not bound a dispatcher, falls back to a no-op dispatcher that
 * simply returns the event unchanged — keeping `createInjection()` total and avoiding
 * a hard dependency on a userland binding the framework should have provided.
 *
 * @implements InjectorInterface<EventDispatcherInterface>
 */
final class EventDispatcherProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly EventCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): EventDispatcherInterface
    {
        /** @var EventDispatcherInterface $original */
        $original = $this->resolveUnderlying(
            $this->container,
            $this->binder,
            EventDispatcherInterface::class,
            self::nullDispatcher(),
        );

        return new EventDispatcherInterfaceProxy($original, $this->collector);
    }

    private static function nullDispatcher(): EventDispatcherInterface
    {
        return new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
    }
}
