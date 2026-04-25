<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Wildcard value that preserves the historical "allow any origin" behaviour.
     */
    public const WILDCARD = '*';

    /**
     * @var list<string>
     */
    private readonly array $allowedOrigins;

    /**
     * @param list<string> $allowedOrigins Either a list of concrete origins
     *                                     (e.g. `['http://localhost:5173']`)
     *                                     or `[self::WILDCARD]` to allow any
     *                                     origin. Empty list disables CORS
     *                                     headers entirely.
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        array $allowedOrigins = [self::WILDCARD],
    ) {
        $this->allowedOrigins = array_values($allowedOrigins);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(204);
        } else {
            $response = $handler->handle($request);
        }

        if ($this->allowedOrigins === []) {
            return $response;
        }

        $allowOrigin = $this->resolveAllowOrigin($request->getHeaderLine('Origin'));

        if ($allowOrigin === null) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader(
                'Access-Control-Allow-Headers',
                'Content-Type, Authorization, X-Debug-Token, X-Requested-With, X-Acp-Session',
            )
            ->withHeader('Access-Control-Max-Age', '86400');

        // When the allowlist contains specific origins, cache the response per
        // origin so shared caches don't mix responses for different callers.
        if ($allowOrigin !== self::WILDCARD) {
            $response = $response->withAddedHeader('Vary', 'Origin');
        }

        return $response;
    }

    private function resolveAllowOrigin(string $requestOrigin): ?string
    {
        if (in_array(self::WILDCARD, $this->allowedOrigins, true)) {
            return self::WILDCARD;
        }

        if ($requestOrigin === '') {
            return null;
        }

        return in_array($requestOrigin, $this->allowedOrigins, true) ? $requestOrigin : null;
    }
}
