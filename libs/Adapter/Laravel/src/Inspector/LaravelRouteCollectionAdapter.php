<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use Illuminate\Routing\Router;

/**
 * Adapts Laravel's Router to the route collection interface
 * expected by RoutingController.
 */
final class LaravelRouteCollectionAdapter
{
    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * @return list<LaravelRouteAdapter>
     */
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->router->getRoutes() as $route) {
            $routes[] = new LaravelRouteAdapter($route);
        }
        return $routes;
    }
}
