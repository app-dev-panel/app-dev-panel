<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Http;

use Yiisoft\Router\RouteCollectorInterface;

final class RouteCollectorWrapper
{
    public function __construct(
        private array $middlewareDefinitions,
    ) {
    }

    public function wrap(RouteCollectorInterface $routeCollector): void
    {
        $routeCollector->prependMiddleware(...$this->middlewareDefinitions);
    }
}
