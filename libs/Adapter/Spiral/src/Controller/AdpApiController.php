<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Controller;

use AppDevPanel\Api\ApiApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Thin PSR-15 handler that wraps the framework-agnostic {@see ApiApplication}.
 *
 * Spiral is PSR-7/PSR-15 native, so this is a direct pass-through — unlike the Laravel
 * and Symfony adapters, no Request/Response conversion is needed.
 *
 * Useful when the host app prefers a concrete controller class over the
 * {@see \AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware} middleware-based integration.
 */
final class AdpApiController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ApiApplication $apiApplication,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->apiApplication->handle($request);
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
