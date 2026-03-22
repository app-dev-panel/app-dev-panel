<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Collector;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

/**
 * Extracts route data from Laravel's router and feeds it to the Kernel RouterCollector.
 *
 * Called by DebugMiddleware after route matching.
 */
final class RouterDataExtractor
{
    public function __construct(
        private readonly RouterCollector $routerCollector,
        private readonly Router $router,
    ) {}

    public function extract(Request $request): void
    {
        $route = $request->route();

        if ($route !== null) {
            /** @var \Illuminate\Routing\Route $route */
            $this->routerCollector->collectMatchedRoute([
                'matchTime' => 0,
                'name' => $route->getName() ?? $route->uri(),
                'pattern' => '/' . ltrim($route->uri(), '/'),
                'arguments' => $route->parameters(),
                'host' => $route->getDomain(),
                'uri' => $request->getRequestUri(),
                'action' => $route->getActionName(),
                'middlewares' => array_values(array_filter($route->gatherMiddleware(), 'is_string')),
            ]);
        }

        $routes = [];
        foreach ($this->router->getRoutes() as $registeredRoute) {
            /** @var \Illuminate\Routing\Route $registeredRoute */
            $routes[] = [
                'name' => $registeredRoute->getName() ?? $registeredRoute->uri(),
                'pattern' => '/' . ltrim($registeredRoute->uri(), '/'),
                'methods' => $registeredRoute->methods() ?: ['ANY'],
                'host' => $registeredRoute->getDomain(),
            ];
        }
        $this->routerCollector->collectRoutes($routes);
    }
}
