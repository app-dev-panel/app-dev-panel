<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class MiddlewareDispatcherMiddleware implements MiddlewareInterface
{
    public function __construct(
        public MiddlewareDispatcher $middlewareDispatcher
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewareDispatcher->dispatch($request, $handler);
    }
}
