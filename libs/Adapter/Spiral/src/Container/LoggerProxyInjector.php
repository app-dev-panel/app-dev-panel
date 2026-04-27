<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;

/**
 * Spiral container injector that wraps any `Psr\Log\LoggerInterface` resolution
 * with {@see LoggerInterfaceProxy} so logged messages are forwarded to {@see LogCollector}.
 *
 * Registered by {@see \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::boot()}
 * via `$binder->bindInjector(LoggerInterface::class, self::class)`. The bootloader also
 * captures the application's original logger through {@see InjectorTrait::setUnderlying()}
 * before replacing the binding — that captured instance becomes the proxy's inner logger.
 *
 * @implements InjectorInterface<LoggerInterface>
 */
final class LoggerProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly LogCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): LoggerInterface
    {
        /** @var LoggerInterface $original */
        $original = $this->resolveUnderlying($this->container, $this->binder, LoggerInterface::class, new NullLogger());

        return new LoggerInterfaceProxy($original, $this->collector);
    }
}
