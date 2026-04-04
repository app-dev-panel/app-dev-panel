<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\RouteCollectionInterface;

final readonly class RouterAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CurrentRoute $currentRoute,
        private RouteCollectionInterface $routeCollection,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // The UrlMatcherInterfaceProxy intercepts route matching (before this action
        // runs) and feeds match timing to RouterCollector. The RouterDataExtractor
        // extracts matched route and route list from the request on AfterRequest.
        // This fixture just uses the router to verify it works.
        $routes = [];
        foreach ($this->routeCollection->getRoutes() as $route) {
            $routes[] = [
                'name' => $route->getData('name'),
                'pattern' => $route->getData('pattern'),
                'methods' => $route->getData('methods'),
            ];
        }

        return $this->responseFactory->createResponse([
            'fixture' => 'router:basic',
            'status' => 'ok',
            'currentRoute' => $this->currentRoute->getName(),
            'routeCount' => count($routes),
        ]);
    }
}
