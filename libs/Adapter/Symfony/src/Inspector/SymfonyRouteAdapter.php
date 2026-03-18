<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use Symfony\Component\Routing\Route;

/**
 * Wraps a single Symfony Route to provide the __debugInfo() format
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController}.
 */
final class SymfonyRouteAdapter
{
    public function __construct(
        private readonly string $name,
        private readonly Route $route,
    ) {}

    public function __debugInfo(): array
    {
        $defaults = $this->route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        unset($defaults['_controller']);

        $middlewares = [];
        if ($controller !== null) {
            $middlewares[] = $controller;
        }

        return [
            'name' => $this->name,
            'hosts' => $this->route->getHost() !== '' ? [$this->route->getHost()] : [],
            'pattern' => $this->route->getPath(),
            'methods' => $this->route->getMethods() ?: ['ANY'],
            'defaults' => $defaults,
            'override' => 0,
            'middlewares' => $middlewares,
        ];
    }
}
