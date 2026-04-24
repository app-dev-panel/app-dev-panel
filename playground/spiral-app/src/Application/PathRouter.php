<?php

declare(strict_types=1);

namespace App\Application;

use AppDevPanel\Kernel\Collector\RouterCollector;
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
 * Returns JSON-encoded arrays when a controller returns an array. Feeds the matched route
 * into `RouterCollector` so the debug panel shows route info for every request.
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
        $startedAt = microtime(true);
        $path = $request->getUri()->getPath();
        $class = $this->routes[$path] ?? $this->fallback;

        $this->feedRouterCollector($request, $path, $class, microtime(true) - $startedAt);

        $handler = $this->container->get($class);

        $result = $handler instanceof RequestHandlerInterface ? $handler->handle($request) : $handler($request);

        return $this->normalize($result);
    }

    private function feedRouterCollector(
        ServerRequestInterface $request,
        string $matchedPath,
        string $handlerClass,
        float $matchTime,
    ): void {
        if (!$this->container->has(RouterCollector::class)) {
            return;
        }

        /** @var RouterCollector $collector */
        $collector = $this->container->get(RouterCollector::class);

        $collector->collectMatchedRoute([
            'matchTime' => $matchTime,
            'name' => $this->nameFor($handlerClass),
            'pattern' => $matchedPath,
            'arguments' => [],
            'host' => $request->getUri()->getHost(),
            'uri' => (string) $request->getUri(),
            'action' => $handlerClass,
            'middlewares' => [],
        ]);

        $collector->collectRoutes(array_map(
            static fn(string $pattern, string $class): array => [
                'name' => $pattern === '/'
                    ? 'home'
                    : 'test_' . trim(str_replace(['/test/fixtures/', '-'], ['', '_'], $pattern), '_'),
                'pattern' => $pattern,
                'method' => 'GET',
                'action' => $class,
            ],
            array_keys($this->routes),
            array_values($this->routes),
        ));
        $collector->collectMatchTime($matchTime);
    }

    private function nameFor(string $handlerClass): string
    {
        // Controller class → fixture name used by the `router:basic` expectation (`test_router`, `test_logs`, …).
        $short = substr($handlerClass, strrpos($handlerClass, '\\') + 1);
        $base = preg_replace('/(Action|Controller)$/', '', $short) ?? $short;
        $snake = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $base));

        return $snake === 'home' ? 'home' : 'test_' . $snake;
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
