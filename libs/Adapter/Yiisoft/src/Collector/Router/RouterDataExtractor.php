<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Router;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;

/**
 * Extracts route data from Yii router and feeds it to the Kernel RouterCollector.
 *
 * Should be called after request matching (e.g., in AfterRequest event).
 */
final class RouterDataExtractor
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RouterCollector $routerCollector,
    ) {}

    public function extract(): void
    {
        $currentRoute = $this->getCurrentRoute();
        $route = $this->getRouteByCurrentRoute($currentRoute);

        if ($currentRoute !== null && $route !== null) {
            [$middlewares, $action] = $this->getMiddlewaresAndAction($route);

            $this->routerCollector->collectMatchedRoute([
                'matchTime' => 0,
                'name' => $route->getData('name'),
                'pattern' => $route->getData('pattern'),
                'arguments' => $currentRoute->getArguments(),
                'host' => $route->getData('host'),
                'uri' => (string) $currentRoute->getUri(),
                'action' => $action,
                'middlewares' => $middlewares,
            ]);
        }

        $routeCollection = $this->container->has(RouteCollectionInterface::class)
            ? $this->container->get(RouteCollectionInterface::class)
            : null;

        if ($routeCollection !== null) {
            $this->routerCollector->collectRoutes($routeCollection->getRoutes(), $routeCollection->getRouteTree());
        }
    }

    private function getCurrentRoute(): ?CurrentRoute
    {
        return $this->container->has(CurrentRoute::class) ? $this->container->get(CurrentRoute::class) : null;
    }

    private function getRouteByCurrentRoute(?CurrentRoute $currentRoute): ?Route
    {
        if ($currentRoute === null) {
            return null;
        }
        $reflection = new ReflectionObject($currentRoute);
        $reflectionProperty = $reflection->getProperty('route');

        return $reflectionProperty->getValue($currentRoute);
    }

    private function getMiddlewaresAndAction(?Route $route): array
    {
        if ($route === null) {
            return [[], null];
        }

        $middlewares = $route->getData('enabledMiddlewares');
        $action = array_pop($middlewares);

        return [$middlewares, $action];
    }
}
