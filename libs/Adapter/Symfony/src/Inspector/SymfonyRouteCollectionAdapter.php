<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use Symfony\Component\Routing\RouterInterface;

/**
 * Adapts Symfony's RouterInterface to the route collection interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController}.
 */
final class SymfonyRouteCollectionAdapter
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    /**
     * @return list<SymfonyRouteAdapter>
     */
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $routes[] = new SymfonyRouteAdapter($name, $route);
        }
        return $routes;
    }
}
