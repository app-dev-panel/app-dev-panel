<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use Psr\Http\Message\ServerRequestInterface;
use Spiral\Router\RouteInterface;
use Spiral\Router\RouterInterface;
use Throwable;

/**
 * Adapts Spiral's {@see RouterInterface} to the duck-typed URL matcher contract
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 *
 * The controller calls `match(ServerRequestInterface $request): SpiralMatchResult`.
 *
 * Spiral's `RouterInterface::handle()` would actually execute the matched route handler —
 * we don't want that during inspection. `Router::matchRoute()` is `protected`, so to find
 * the matching route without executing it we iterate `RouterInterface::getRoutes()` and
 * call `RouteInterface::match($request)` on each (the documented public API for matching).
 */
final class SpiralUrlMatcherAdapter
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    public function match(ServerRequestInterface $request): SpiralMatchResult
    {
        try {
            $routes = $this->router->getRoutes();
        } catch (Throwable) {
            return new SpiralMatchResult(false);
        }

        foreach ($routes as $route) {
            if (!$route instanceof RouteInterface) {
                continue;
            }
            try {
                $matched = $route->match($request);
            } catch (Throwable) {
                continue;
            }
            if ($matched === null) {
                continue;
            }

            try {
                $defaults = $matched->getDefaults();
            } catch (Throwable) {
                $defaults = [];
            }
            $controller = $defaults['controller'] ?? null;
            $action = $defaults['action'] ?? null;
            $label = null;
            if (is_string($controller) && $controller !== '') {
                $label = is_string($action) && $action !== '' ? $controller . '::' . $action : $controller;
            }

            return new SpiralMatchResult(true, $label);
        }

        return new SpiralMatchResult(false);
    }
}
