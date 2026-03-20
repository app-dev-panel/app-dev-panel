<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Extracts route data from Symfony's router and feeds it to the Kernel RouterCollector.
 *
 * Called by HttpSubscriber after route matching (kernel.response event).
 */
final class RouterDataExtractor
{
    public function __construct(
        private readonly RouterCollector $routerCollector,
        private readonly ?RouterInterface $router = null,
    ) {}

    public function extract(Request $request): void
    {
        $routeName = $request->attributes->get('_route');

        if ($routeName !== null) {
            $controller = $request->attributes->get('_controller');
            $routeParams = $request->attributes->get('_route_params', []);

            $pattern = $routeName;
            $host = null;

            // Try to get the actual route pattern from the router
            if ($this->router !== null) {
                $routeObject = $this->router->getRouteCollection()->get($routeName);
                if ($routeObject !== null) {
                    $pattern = $routeObject->getPath();
                    $host = $routeObject->getHost() !== '' ? $routeObject->getHost() : null;
                }
            }

            $this->routerCollector->collectMatchedRoute([
                'matchTime' => 0,
                'name' => $routeName,
                'pattern' => $pattern,
                'arguments' => $routeParams,
                'host' => $host,
                'uri' => $request->getRequestUri(),
                'action' => $controller,
                'middlewares' => [],
            ]);
        }

        if ($this->router !== null) {
            $routes = [];
            foreach ($this->router->getRouteCollection() as $name => $route) {
                $routes[] = [
                    'name' => $name,
                    'pattern' => $route->getPath(),
                    'methods' => $route->getMethods() ?: ['ANY'],
                    'host' => $route->getHost() !== '' ? $route->getHost() : null,
                ];
            }
            $this->routerCollector->collectRoutes($routes);
        }
    }
}
