<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use Spiral\Router\RouteInterface;
use Spiral\Router\RouterInterface;
use Throwable;

/**
 * Adapts Spiral's {@see RouterInterface} to the duck-typed route collection interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::routes()}.
 *
 * For each `RouteInterface` in the underlying router this adapter emits a wrapper that
 * exposes `__debugInfo()` returning `[name, hosts, pattern, methods, defaults, override,
 * middlewares]` — the exact shape the controller serializes.
 *
 * Bound under the `'router'` container alias only when `spiral/router` is installed
 * (guarded with `interface_exists` in the bootloader); apps that use a custom router
 * keep the default 501 response from the inspector controller.
 */
final class SpiralRouteCollectionAdapter
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    /**
     * @return list<object>
     */
    public function getRoutes(): array
    {
        $out = [];
        try {
            $routes = $this->router->getRoutes();
        } catch (Throwable) {
            return [];
        }

        foreach ($routes as $name => $route) {
            if (!$route instanceof RouteInterface) {
                continue;
            }
            $out[] = new SpiralRouteAdapter(is_string($name) ? $name : '', $route);
        }

        return $out;
    }
}
