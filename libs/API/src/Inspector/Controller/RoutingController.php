<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\VarDumper\VarDumper;

final class RoutingController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function routes(RouteCollectionInterface $routeCollection): ResponseInterface
    {
        $routes = [];
        foreach ($routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'hosts' => $data['hosts'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
                'defaults' => $data['defaults'],
                'override' => $data['override'],
                'middlewares' => $data['middlewareDefinitions'] ?? [],
            ];
        }
        $response = VarDumper::create($routes)->asPrimitives(5);

        return $this->responseFactory->createResponse($response);
    }

    public function checkRoute(
        ServerRequestInterface $request,
        UrlMatcherInterface $matcher,
        ServerRequestFactoryInterface $serverRequestFactory,
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $path = $queryParams['route'] ?? null;
        if ($path === null) {
            return $this->responseFactory->createResponse([
                'message' => 'Path is not specified.',
            ], 422);
        }
        $path = trim($path);

        $method = 'GET';
        if (str_contains($path, ' ')) {
            [$possibleMethod, $restPath] = explode(' ', $path, 2);
            if (in_array($possibleMethod, Method::ALL, true)) {
                $method = $possibleMethod;
                $path = $restPath;
            }
        }
        $request = $serverRequestFactory->createServerRequest($method, $path);

        $result = $matcher->match($request);
        if (!$result->isSuccess()) {
            return $this->responseFactory->createResponse([
                'result' => false,
            ]);
        }

        $route = $result->route();
        $reflection = new \ReflectionObject($route);
        $property = $reflection->getProperty('middlewareDefinitions');
        $middlewareDefinitions = $property->getValue($route);
        $action = end($middlewareDefinitions);

        return $this->responseFactory->createResponse([
            'result' => true,
            'action' => $action,
        ]);
    }
}
