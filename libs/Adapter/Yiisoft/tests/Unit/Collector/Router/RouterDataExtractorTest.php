<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Collector\Router;

use AppDevPanel\Adapter\Yiisoft\Collector\Router\RouterDataExtractor;
use AppDevPanel\Kernel\Collector\RouterCollector;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;

final class RouterDataExtractorTest extends TestCase
{
    public function testExtractWithMatchedRoute(): void
    {
        $route = Route::get('/test/{id}')
            ->name('test.view')
            ->middleware('AuthMiddleware')
            ->action('TestController::view');
        $currentRoute = new CurrentRoute();

        // Set the route on CurrentRoute via reflection
        $ref = new \ReflectionObject($currentRoute);
        $routeProperty = $ref->getProperty('route');
        $routeProperty->setValue($currentRoute, $route);

        // Set arguments
        $argsProperty = $ref->getProperty('arguments');
        $argsProperty->setValue($currentRoute, ['id' => '42']);

        // Set URI
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('__toString')->willReturn('/test/42');
        $uriProperty = $ref->getProperty('uri');
        $uriProperty->setValue($currentRoute, $uri);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnCallback(fn(string $id) => match ($id) {
                CurrentRoute::class => true,
                RouteCollectionInterface::class => false,
                default => false,
            });
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id) => match ($id) {
                CurrentRoute::class => $currentRoute,
                default => null,
            });

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($container, $collector);
        $extractor->extract();

        $collected = $collector->getCollected();
        $this->assertNotNull($collected['currentRoute']);
        $this->assertSame('test.view', $collected['currentRoute']['name']);
        $this->assertSame('/test/{id}', $collected['currentRoute']['pattern']);
        $this->assertSame(['id' => '42'], $collected['currentRoute']['arguments']);
        $this->assertSame('/test/42', $collected['currentRoute']['uri']);
    }

    public function testExtractWithNoCurrentRoute(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($container, $collector);
        $extractor->extract();

        $collected = $collector->getCollected();
        $this->assertNull($collected['currentRoute']);
    }

    public function testExtractWithRouteCollection(): void
    {
        $routes = [['name' => 'home', 'pattern' => '/']];
        $routeTree = ['/' => ['name' => 'home']];

        $routeCollection = $this->createMock(RouteCollectionInterface::class);
        $routeCollection->method('getRoutes')->willReturn($routes);
        $routeCollection->method('getRouteTree')->willReturn($routeTree);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnCallback(fn(string $id) => match ($id) {
                CurrentRoute::class => false,
                RouteCollectionInterface::class => true,
                default => false,
            });
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id) => match ($id) {
                RouteCollectionInterface::class => $routeCollection,
                default => null,
            });

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($container, $collector);
        $extractor->extract();

        $collected = $collector->getCollected();
        $this->assertSame($routes, $collected['routes']);
        $this->assertSame($routeTree, $collected['routesTree']);
    }

    public function testExtractWithCurrentRouteButNoRouteObject(): void
    {
        $currentRoute = new CurrentRoute();
        // No route set — getRouteByCurrentRoute should return null via reflection

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnCallback(fn(string $id) => match ($id) {
                CurrentRoute::class => true,
                RouteCollectionInterface::class => false,
                default => false,
            });
        $container
            ->method('get')
            ->willReturnCallback(fn(string $id) => match ($id) {
                CurrentRoute::class => $currentRoute,
                default => null,
            });

        $collector = new RouterCollector();
        $collector->startup();

        $extractor = new RouterDataExtractor($container, $collector);
        $extractor->extract();

        $collected = $collector->getCollected();
        $this->assertNull($collected['currentRoute']);
    }
}
