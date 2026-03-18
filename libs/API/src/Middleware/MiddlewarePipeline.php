<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    private RequestHandlerInterface $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = new class($this->middlewares, $this->fallbackHandler) implements RequestHandlerInterface {
            private int $index = 0;

            /**
             * @param MiddlewareInterface[] $middlewares
             */
            public function __construct(
                private readonly array $middlewares,
                private readonly RequestHandlerInterface $fallbackHandler,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (!isset($this->middlewares[$this->index])) {
                    return $this->fallbackHandler->handle($request);
                }

                $middleware = $this->middlewares[$this->index];
                $this->index++;

                return $middleware->process($request, $this);
            }
        };

        return $handler->handle($request);
    }
}
