<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Adapts Symfony's RouterInterface::match() to the URL matcher interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 */
final class SymfonyUrlMatcherAdapter
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    public function match(ServerRequestInterface $request): SymfonyMatchResult
    {
        $path = $request->getUri()->getPath();

        try {
            $params = $this->router->match($path);
            $controller = $params['_controller'] ?? null;

            return new SymfonyMatchResult(true, $controller);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return new SymfonyMatchResult(false);
        }
    }
}
