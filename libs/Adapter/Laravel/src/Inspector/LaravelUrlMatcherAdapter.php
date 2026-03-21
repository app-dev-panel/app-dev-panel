<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use Illuminate\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adapts Laravel's Router::match() to the URL matcher interface
 * expected by RoutingController::checkRoute().
 */
final class LaravelUrlMatcherAdapter
{
    public function __construct(
        private readonly Router $router,
    ) {}

    public function match(ServerRequestInterface $request): LaravelMatchResult
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        try {
            $laravelRequest = \Illuminate\Http\Request::create($path, $method);
            $route = $this->router->getRoutes()->match($laravelRequest);
            $action = $route->getActionName();

            return new LaravelMatchResult(true, $action !== 'Closure' ? $action : null);
        } catch (\Throwable) {
            return new LaravelMatchResult(false);
        }
    }
}
