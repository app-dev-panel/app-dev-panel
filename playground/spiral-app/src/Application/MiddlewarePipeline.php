<?php

declare(strict_types=1);

namespace App\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal PSR-15 middleware pipeline — walks the middleware list and delegates to the final handler.
 */
final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private array $middlewares,
        private readonly RequestHandlerInterface $finalHandler,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware === null) {
            return $this->finalHandler->handle($request);
        }

        return $middleware->process($request, $this);
    }
}
