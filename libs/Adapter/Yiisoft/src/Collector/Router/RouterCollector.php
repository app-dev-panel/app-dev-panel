<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Router;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;

final class RouterCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private float $matchTime = 0;

    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function collect(float $matchTime): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->matchTime = $matchTime;
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $routeCollection = $this->container->has(RouteCollectionInterface::class)
            ? $this->container->get(RouteCollectionInterface::class)
            : null;

        $currentRoute = $this->getCurrentRoute();
        $route = $this->getRouteByCurrentRoute($currentRoute);
        [$middlewares, $action] = $this->getMiddlewaresAndAction($route);

        $result = [
            'currentRoute' => null,
        ];
        if ($currentRoute !== null && $route !== null) {
            $result['currentRoute'] = [
                'matchTime' => $this->matchTime,
                'name' => $route->getData('name'),
                'pattern' => $route->getData('pattern'),
                'arguments' => $currentRoute->getArguments(),
                'host' => $route->getData('host'),
                'uri' => (string) $currentRoute->getUri(),
                'action' => $action,
                'middlewares' => $middlewares,
            ];
        }
        if ($routeCollection !== null) {
            $result['routesTree'] = $routeCollection->getRouteTree();
            $result['routes'] = $routeCollection->getRoutes();
            $result['routeTime'] = $this->matchTime;
        }
        return $result;
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        $currentRoute = $this->getCurrentRoute();
        $route = $this->getRouteByCurrentRoute($currentRoute);

        if ($currentRoute === null || $route === null) {
            return [];
        }

        [$middlewares, $action] = $this->getMiddlewaresAndAction($route);

        return [
            'matchTime' => $this->matchTime,
            'name' => $route->getData('name'),
            'pattern' => $route->getData('pattern'),
            'arguments' => $currentRoute->getArguments(),
            'host' => $route->getData('host'),
            'uri' => (string) $currentRoute->getUri(),
            'action' => $action,
            'middlewares' => $middlewares,
        ];
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
