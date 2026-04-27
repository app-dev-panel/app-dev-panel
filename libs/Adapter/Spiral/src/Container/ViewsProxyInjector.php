<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Adapter\Spiral\View\TracingViews;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Views\ViewInterface;
use Spiral\Views\ViewsInterface;

/**
 * Spiral container injector that wraps any `Spiral\Views\ViewsInterface` resolution
 * with {@see TracingViews} so every render is forwarded to {@see TemplateCollector}.
 *
 * Only registered by the bootloader when `interface_exists(ViewsInterface::class)` —
 * `spiral/views` is an optional package.
 *
 * @implements InjectorInterface<ViewsInterface>
 */
final class ViewsProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly TemplateCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): ViewsInterface
    {
        /** @var ViewsInterface $original */
        $original = $this->resolveUnderlying($this->container, $this->binder, ViewsInterface::class, self::nullViews());

        return new TracingViews($original, $this->collector);
    }

    private static function nullViews(): ViewsInterface
    {
        return new class implements ViewsInterface {
            public function render(string $path, array $data = []): string
            {
                return '';
            }

            public function get(string $path): ViewInterface
            {
                throw new \RuntimeException('No upstream views bound to the container.');
            }

            public function compile(string $path): void
            {
                // no-op
            }

            public function reset(string $path): void
            {
                // no-op
            }
        };
    }
}
