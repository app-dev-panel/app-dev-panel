<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Inspector\Primitives;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RoutingController
{
    private const HTTP_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ?object $routeCollection = null,
        private readonly ?object $urlMatcher = null,
    ) {}

    public function routes(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->routeCollection === null) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Route inspection requires framework integration.',
            ], 501);
        }

        $routes = [];
        foreach ($this->routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'hosts' => $data['hosts'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
                'defaults' => $data['defaults'],
                'override' => $data['override'],
                'middlewares' => $data['middlewares'] ?? $data['middlewareDefinitions'] ?? [],
            ];
        }
        $response = Primitives::dump($routes, 5);

        return $this->responseFactory->createJsonResponse($response);
    }

    public function checkRoute(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->urlMatcher === null) {
            return $this->responseFactory->createJsonResponse([
                'error' => 'Route checking requires framework integration.',
            ], 501);
        }

        $queryParams = $request->getQueryParams();
        $path = $queryParams['route'] ?? null;
        if ($path === null) {
            return $this->responseFactory->createJsonResponse([
                'message' => 'Path is not specified.',
            ], 422);
        }
        $path = trim($path);

        $method = 'GET';
        if (str_contains($path, ' ')) {
            [$possibleMethod, $restPath] = explode(' ', $path, 2);
            if (in_array($possibleMethod, self::HTTP_METHODS, true)) {
                $method = $possibleMethod;
                $path = $restPath;
            }
        }

        $serverRequestFactory = new \GuzzleHttp\Psr7\ServerRequest($method, $path);
        $result = $this->urlMatcher->match($serverRequestFactory);

        if (!$result->isSuccess()) {
            return $this->responseFactory->createJsonResponse([
                'result' => false,
            ]);
        }

        $route = $result->route();
        $reflection = new \ReflectionObject($route);
        $propertyName = $reflection->hasProperty('middlewareDefinitions') ? 'middlewareDefinitions' : 'middlewares';
        $property = $reflection->getProperty($propertyName);
        $middlewareDefinitions = $property->getValue($route);
        $action = end($middlewareDefinitions);

        return $this->responseFactory->createJsonResponse([
            'result' => true,
            'action' => $action,
        ]);
    }
}
