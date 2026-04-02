<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds Cache-Control headers to API responses.
 *
 * Immutable debug entry data (view, dump, object, summary) is cached aggressively
 * since entries never change after being written. All other endpoints get no-cache.
 */
final class CacheControlMiddleware implements MiddlewareInterface
{
    /**
     * Path prefixes for immutable debug entry data.
     * These endpoints return data that never changes once written.
     */
    private const array IMMUTABLE_PREFIXES = [
        '/debug/api/view/',
        '/debug/api/dump/',
        '/debug/api/object/',
        '/debug/api/summary/',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($request->getMethod() !== 'GET') {
            return $response->withHeader('Cache-Control', 'no-store');
        }

        $path = $request->getUri()->getPath();

        foreach (self::IMMUTABLE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $response->withHeader('Cache-Control', 'public, max-age=31536000, immutable');
            }
        }

        return $response->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
