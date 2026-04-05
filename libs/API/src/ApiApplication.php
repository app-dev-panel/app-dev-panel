<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Api\Middleware\CacheControlMiddleware;
use AppDevPanel\Api\Middleware\CorsMiddleware;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\Middleware\MiddlewarePipeline;
use AppDevPanel\Api\Router\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApiApplication implements RequestHandlerInterface
{
    private Router $router;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        ?Router $router = null,
    ) {
        $this->router = $router ?? new Router();
        if ($router === null) {
            ApiRoutes::register($this->router);
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Normalize: remove trailing slash
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $result = $this->router->match($method, $path);

        if ($result === null) {
            // Handle OPTIONS for CORS preflight on any path
            if ($method === 'OPTIONS') {
                return $this->responseFactory
                    ->createResponse(204)
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                    ->withHeader(
                        'Access-Control-Allow-Headers',
                        'Content-Type, Authorization, X-Debug-Token, X-Requested-With, X-Acp-Session',
                    )
                    ->withHeader('Access-Control-Max-Age', '86400');
            }

            return $this->jsonErrorResponse(404, 'Not found.');
        }

        $route = $result['route'];
        $params = $result['params'];

        // Add route parameters as request attributes
        foreach ($params as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        // Build middleware pipeline based on route prefix
        $isInspector = str_starts_with($path, '/inspect/api');
        $isPanel = $path === '/debug' || str_starts_with($path, '/debug') && !str_starts_with($path, '/debug/api');
        $pipeline = $this->buildPipeline($request, $isInspector, $isPanel);

        // The final handler resolves and calls the controller
        $controllerHandler = $this->createControllerHandler($route->handler, $request);

        $pipelineRunner = new MiddlewarePipeline($controllerHandler);

        foreach ($pipeline as $middleware) {
            $pipelineRunner->pipe($middleware);
        }

        return $pipelineRunner->handle($request);
    }

    /**
     * @return \Psr\Http\Server\MiddlewareInterface[]
     */
    private function buildPipeline(ServerRequestInterface $request, bool $isInspector, bool $isPanel): array
    {
        $middlewares = [];
        $path = $request->getUri()->getPath();
        $isMcp = $path === '/inspect/api/mcp';

        // CORS
        $middlewares[] = new CorsMiddleware($this->responseFactory);

        // Panel routes only need CORS and IP filter — no JSON wrapping, no token auth
        if ($isPanel) {
            if ($this->container->has(IpFilterMiddleware::class)) {
                $middlewares[] = $this->container->get(IpFilterMiddleware::class);
            }

            return $middlewares;
        }

        // IP filter
        if ($this->container->has(IpFilterMiddleware::class)) {
            $middlewares[] = $this->container->get(IpFilterMiddleware::class);
        }

        // Token auth
        if ($this->container->has(TokenAuthMiddleware::class)) {
            $middlewares[] = $this->container->get(TokenAuthMiddleware::class);
        }

        // Response wrapper — skip for MCP (JSON-RPC has its own response format)
        if (!$isMcp && $this->container->has(ResponseDataWrapper::class)) {
            $middlewares[] = $this->container->get(ResponseDataWrapper::class);
        }

        // Cache-Control headers
        $middlewares[] = new CacheControlMiddleware();

        // Inspector proxy (only for /inspect/api)
        if ($isInspector && $this->container->has(InspectorProxyMiddleware::class)) {
            $middlewares[] = $this->container->get(InspectorProxyMiddleware::class);
        }

        return $middlewares;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    private function createControllerHandler(array $handler, ServerRequestInterface $request): RequestHandlerInterface
    {
        $container = $this->container;
        $responseFactory = $this->responseFactory;
        $streamFactory = $this->streamFactory;

        return new class($container, $handler, $responseFactory, $streamFactory) implements RequestHandlerInterface {
            /**
             * @param array{0: class-string, 1: string} $handler
             */
            public function __construct(
                private readonly ContainerInterface $container,
                private readonly array $handler,
                private readonly ResponseFactoryInterface $responseFactory,
                private readonly StreamFactoryInterface $streamFactory,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                [$controllerClass, $method] = $this->handler;

                $controller = $this->container->get($controllerClass);

                return $controller->$method($request);
            }
        };
    }

    private function jsonErrorResponse(int $status, string $message): ResponseInterface
    {
        $body = json_encode([
            'error' => $message,
            'success' => false,
        ], JSON_THROW_ON_ERROR);

        return $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}
