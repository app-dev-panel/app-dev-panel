<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteAdapter;
use AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class SymfonyRouteCollectionAdapterTest extends TestCase
{
    public function testGetRoutesReturnsAdaptedRoutes(): void
    {
        $collection = new RouteCollection();
        $collection->add(
            'home',
            new Route('/', defaults: ['_controller' => 'App\\HomeController::index'], methods: ['GET']),
        );
        $collection->add(
            'api_users',
            new Route('/api/users', defaults: ['_controller' => 'App\\ApiController::users'], methods: ['GET', 'POST']),
        );

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($collection);

        $adapter = new SymfonyRouteCollectionAdapter($router);
        $routes = $adapter->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf(SymfonyRouteAdapter::class, $routes);
    }

    public function testGetRoutesWithEmptyCollection(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn(new RouteCollection());

        $adapter = new SymfonyRouteCollectionAdapter($router);

        $this->assertSame([], $adapter->getRoutes());
    }
}
