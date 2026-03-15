<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Http;

use AppDevPanel\Api\Debug\Http\RouteCollectorWrapper;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\RouteCollectorInterface;

final class RouteCollectorWrapperTest extends TestCase
{
    public function testWrap(): void
    {
        $middlewareDefinitions = ['middleware1', 'middleware2'];

        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->expects($this->once())->method('prependMiddleware')->with('middleware1', 'middleware2');

        $wrapper = new RouteCollectorWrapper($middlewareDefinitions);
        $wrapper->wrap($routeCollector);
    }

    public function testWrapWithEmptyMiddleware(): void
    {
        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->expects($this->once())->method('prependMiddleware');

        $wrapper = new RouteCollectorWrapper([]);
        $wrapper->wrap($routeCollector);
    }
}
