<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use Spiral\Router\RouteInterface;
use Throwable;

/**
 * Wraps a single Spiral {@see RouteInterface} to expose the `__debugInfo()` shape
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::routes()}.
 *
 * The controller reads `name`, `hosts`, `pattern`, `methods`, `defaults`, `override`,
 * `middlewares` from the returned array.
 */
final class SpiralRouteAdapter
{
    public function __construct(
        private readonly string $name,
        private readonly RouteInterface $route,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $pattern = '';
        try {
            $uriHandler = $this->route->getUriHandler();
            $pattern = $uriHandler->getPattern() ?? '';
        } catch (Throwable) {
            // Some custom routes may not return a UriHandler — leave pattern empty.
        }

        $defaults = [];
        try {
            $defaults = $this->route->getDefaults();
        } catch (Throwable) {
            // Optional.
        }

        $methods = [];
        try {
            $methods = $this->route->getVerbs();
        } catch (Throwable) {
            // Optional.
        }
        if ($methods === []) {
            $methods = ['ANY'];
        }

        $controller = $defaults['controller'] ?? null;
        $action = $defaults['action'] ?? null;

        $middlewares = [];
        if (is_string($controller) && $controller !== '') {
            $middlewares[] = is_string($action) && $action !== '' ? $controller . '::' . $action : $controller;
        }

        return [
            'name' => $this->name,
            'hosts' => [],
            'pattern' => $pattern,
            'methods' => $methods,
            'defaults' => $defaults,
            'override' => 0,
            'middlewares' => $middlewares,
        ];
    }
}
