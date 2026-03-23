<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use Illuminate\Routing\Route;

/**
 * Wraps a single Laravel Route to provide the __debugInfo() format
 * expected by RoutingController.
 */
final class LaravelRouteAdapter
{
    public function __construct(
        private readonly Route $route,
    ) {}

    public function __debugInfo(): array
    {
        $action = $this->route->getActionName();
        $middlewares = [];

        if ($action !== 'Closure') {
            $middlewares[] = $action;
        }

        $routeMiddlewares = $this->route->gatherMiddleware();
        foreach ($routeMiddlewares as $m) {
            if (!is_string($m)) {
                continue;
            }

            $middlewares[] = $m;
        }

        return [
            'name' => $this->route->getName() ?? $this->route->uri(),
            'hosts' => $this->route->getDomain() !== null ? [$this->route->getDomain()] : [],
            'pattern' => '/' . ltrim($this->route->uri(), '/'),
            'methods' => $this->route->methods() ?: ['ANY'],
            'defaults' => $this->route->defaults,
            'override' => 0,
            'middlewares' => $middlewares,
        ];
    }
}
