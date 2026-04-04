<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/test/fixtures/router', name: 'test_router', methods: ['GET'])]
final readonly class RouterAction
{
    public function __construct(
        private RouterInterface $router,
    ) {}

    public function __invoke(): JsonResponse
    {
        // The RouterDataExtractor (called by HttpSubscriber on kernel.response)
        // automatically feeds matched route and all routes to RouterCollector.
        // This fixture just uses the router to verify it works.
        $routes = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $routes[] = [
                'name' => $name,
                'pattern' => $route->getPath(),
                'methods' => $route->getMethods() ?: ['ANY'],
            ];
        }

        return new JsonResponse([
            'fixture' => 'router:basic',
            'status' => 'ok',
            'routeCount' => count($routes),
        ]);
    }
}
