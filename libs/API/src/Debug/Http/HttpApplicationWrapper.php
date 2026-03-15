<?php

declare(strict_types = 1);

namespace AppDevPanel\Api\Debug\Http;

use Closure;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use AppDevPanel\Api\Debug\Middleware\MiddlewareDispatcherMiddleware;
use Yiisoft\Yii\Http\Application;

final class HttpApplicationWrapper
{
    public function __construct(
        private MiddlewareDispatcher $middlewareDispatcher,
        private array $middlewareDefinitions
    ) {
    }

    public function wrap(Application $application): Application
    {
        $middlewareDispatcher = $this->middlewareDispatcher;
        $middlewareDefinitions = $this->middlewareDefinitions;

        $closure = Closure::bind(
            /**
             * @psalm-suppress InaccessibleProperty
             */
            static function (Application $application) use ($middlewareDispatcher, $middlewareDefinitions) {
                $middlewareDispatcher = $middlewareDispatcher->withMiddlewares([
                    ...$middlewareDefinitions,
                    [
                        'class' => MiddlewareDispatcherMiddleware::class,
                        '$middlewareDispatcher' => $application->dispatcher
                    ]
                ]);

                return new Application($middlewareDispatcher, $application->eventDispatcher);

                //                return $application->dispatcher = $middlewareDispatcher;
            },
            null,
            $application
        );

        return $closure($application);
    }
}
