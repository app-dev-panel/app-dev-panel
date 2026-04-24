<?php

declare(strict_types=1);

namespace App\Application;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple path-based dispatcher — maps request path to a controller class in the container.
 *
 * Controllers must be callable (define `__invoke`) or implement {@see RequestHandlerInterface}.
 * Returns JSON-encoded arrays when a controller returns an array.
 */
final class PathRouter implements RequestHandlerInterface
{
    /**
     * @param array<string, class-string> $routes
     * @param class-string $fallback
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly array $routes,
        private readonly string $fallback,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $class = $this->routes[$path] ?? $this->fallback;

        $handler = $this->container->get($class);

        $result = $handler instanceof RequestHandlerInterface ? $handler->handle($request) : $handler($request);

        return $this->normalize($result);
    }

    private function normalize(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_array($result) || is_string($result)) {
            $body = is_array($result) ? json_encode($result, JSON_THROW_ON_ERROR) : $result;
            $contentType = is_array($result) ? 'application/json' : 'text/html; charset=utf-8';

            return $this->responseFactory
                ->createResponse(200)
                ->withHeader('Content-Type', $contentType)
                ->withBody($this->streamFactory->createStream($body));
        }

        return $this->responseFactory->createResponse(204);
    }
}
