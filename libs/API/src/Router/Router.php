<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Router;

final class Router
{
    /** @var Route[] */
    private array $routes = [];

    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @param Route[] $routes
     */
    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $this->routes[] = $route;
        }
    }

    /**
     * @return array{route: Route, params: array<string, string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        // Normalize path: remove trailing slash except for root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            $params = $route->match($method, $path);
            if ($params !== null) {
                return ['route' => $route, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
