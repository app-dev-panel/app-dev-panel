<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelRouteAdapter;
use AppDevPanel\Adapter\Laravel\Inspector\LaravelRouteCollectionAdapter;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

final class LaravelRouteCollectionAdapterTest extends TestCase
{
    public function testGetRoutesReturnsAdaptedRoutes(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/users', ['uses' => 'UserController@index']));
        $routeCollection->add(new Route(['POST'], '/users', ['uses' => 'UserController@store']));

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelRouteCollectionAdapter($router);
        $routes = $adapter->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertInstanceOf(LaravelRouteAdapter::class, $routes[0]);
        $this->assertInstanceOf(LaravelRouteAdapter::class, $routes[1]);
    }

    public function testGetRoutesReturnsEmptyForNoRoutes(): void
    {
        $routeCollection = new RouteCollection();

        $router = $this->createMock(Router::class);
        $router->method('getRoutes')->willReturn($routeCollection);

        $adapter = new LaravelRouteCollectionAdapter($router);
        $routes = $adapter->getRoutes();

        $this->assertCount(0, $routes);
    }
}
