<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Http;

use Closure;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\MiddlewareDispatcherMiddleware;
use Yiisoft\Yii\Http\Application;

final class HttpApplicationWrapper
{
    public function __construct(
        private MiddlewareDispatcher $middlewareDispatcher,
        private array $middlewareDefinitions,
    ) {
    }

    public function wrap(Application $application): void
    {
        $middlewareDispatcher = $this->middlewareDispatcher;
        $middlewareDefinitions = $this->middlewareDefinitions;

        $closure = Closure::bind(
            /**
             * @psalm-suppress InaccessibleProperty
             */
            static fn (Application $application) => $application->dispatcher = $middlewareDispatcher->withMiddlewares([
                ...$middlewareDefinitions,
                ['class' => MiddlewareDispatcherMiddleware::class, '$middlewareDispatcher' => $application->dispatcher],
            ]),
            null,
            $application
        );

        $closure($application);
    }
}
