<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Provider;

use AppDevPanel\Api\Debug\Http\RouteCollectorWrapper;
use AppDevPanel\Api\Debug\Middleware\DebugHeaders;
use AppDevPanel\Api\Debug\Provider\DebugApiProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Router\RouteCollectorInterface;

final class DebugApiProviderTest extends TestCase
{
    public function testExtension(): void
    {
        $provider = new DebugApiProvider();

        $this->assertIsArray($provider->getDefinitions());
        $this->assertIsArray($provider->getExtensions());
        $this->assertEmpty($provider->getDefinitions());

        $extensions = $provider->getExtensions();
        $this->assertArrayHasKey(RouteCollectorInterface::class, $extensions);

        $routeCollectorDecorator = $extensions[RouteCollectorInterface::class];
        $this->assertIsCallable($routeCollectorDecorator);

        $middlewares = [DebugHeaders::class];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(RouteCollectorWrapper::class)
            ->willReturn(new RouteCollectorWrapper($middlewares));

        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector
            ->expects($this->once())
            ->method('prependMiddleware')
            ->with(...$middlewares)
            ->willReturn($routeCollector);

        $this->assertSame($routeCollector, $routeCollectorDecorator($container, $routeCollector));
    }
}
