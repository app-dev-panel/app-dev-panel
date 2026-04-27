<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Middleware;

use AppDevPanel\Api\ApiApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that intercepts ADP routes and dispatches them to the ApiApplication.
 *
 * Routes handled:
 *   - `/debug`                → Panel SPA (client-side routed)
 *   - `/debug/api/*`          → Debug data REST API + SSE
 *   - `/inspect/api/*`        → Live inspector API
 *
 * All other requests pass through to the application handler unchanged.
 *
 * Place this middleware as close to the request entry as possible (in front of the router)
 * so ADP owns its URL space and the host framework never sees these requests.
 */
final class AdpApiMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiApplication $apiApplication,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($this->isAdpRoute($path)) {
            return $this->apiApplication->handle($request);
        }

        return $handler->handle($request);
    }

    private function isAdpRoute(string $path): bool
    {
        return (
            $path === '/debug'
            || str_starts_with($path, '/debug/api')
            || str_starts_with($path, '/inspect/api')
            || str_starts_with($path, '/debug/')
        );
    }
}
