<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures HTTP routing data: matched route, match timing, route tree.
 *
 * Framework adapters call collectMatchedRoute() and collectRoutes() with normalized
 * data extracted from their router component.
 */
final class RouterCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?array $currentRoute = null;
    private ?array $routesTree = null;
    private ?array $routes = null;
    private float $matchTime = 0;

    /**
     * Collect the matched route info.
     *
     * @param array{matchTime: float, name: ?string, pattern: string, arguments: array, host: ?string, uri: string, action: mixed, middlewares: array} $routeData
     */
    public function collectMatchedRoute(array $routeData): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->currentRoute = $routeData;
        $this->matchTime = $routeData['matchTime'];
    }

    /**
     * Collect all registered routes.
     */
    public function collectRoutes(array $routes, ?array $routesTree = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->routes = $routes;
        $this->routesTree = $routesTree;
    }

    /**
     * Collect only the match time (lightweight alternative).
     */
    public function collectMatchTime(float $matchTime): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->matchTime = $matchTime;
    }

    public function getCollected(): array
    {
        $result = [
            'currentRoute' => $this->currentRoute,
        ];

        if ($this->routes !== null) {
            $result['routes'] = $this->routes;
            $result['routesTree'] = $this->routesTree;
            $result['routeTime'] = $this->matchTime;
        }

        return $result;
    }

    public function getSummary(): array
    {
        if ($this->currentRoute === null) {
            return [];
        }

        return [
            'router' => $this->currentRoute,
        ];
    }

    protected function reset(): void
    {
        $this->currentRoute = null;
        $this->routesTree = null;
        $this->routes = null;
        $this->matchTime = 0;
    }
}
