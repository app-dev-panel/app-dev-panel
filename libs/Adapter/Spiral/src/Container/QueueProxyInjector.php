<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Adapter\Spiral\Queue\TracingQueue;
use AppDevPanel\Kernel\Collector\QueueCollector;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Queue\OptionsInterface;
use Spiral\Queue\QueueInterface;

/**
 * Spiral container injector that wraps any `Spiral\Queue\QueueInterface` resolution
 * with {@see TracingQueue} so every push is forwarded to {@see QueueCollector}.
 *
 * Only registered by the bootloader when `interface_exists(QueueInterface::class)` —
 * `spiral/queue` is an optional package.
 *
 * @implements InjectorInterface<QueueInterface>
 */
final class QueueProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly QueueCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): QueueInterface
    {
        /** @var QueueInterface $original */
        $original = $this->resolveUnderlying($this->container, $this->binder, QueueInterface::class, self::nullQueue());

        return new TracingQueue($original, $this->collector);
    }

    private static function nullQueue(): QueueInterface
    {
        return new class implements QueueInterface {
            public function push(string $name, array|object $payload = [], ?OptionsInterface $options = null): string
            {
                return '';
            }
        };
    }
}
