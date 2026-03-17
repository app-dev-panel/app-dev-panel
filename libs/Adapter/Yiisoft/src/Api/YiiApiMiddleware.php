<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api;

use AppDevPanel\Api\ApiApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that intercepts /debug/api and /inspect/api requests
 * and delegates them to the ADP ApiApplication.
 *
 * Yii uses PSR-7 natively, so no request/response conversion is needed.
 */
final class YiiApiMiddleware implements MiddlewareInterface
{
    private const API_PREFIXES = ['/debug/api', '/inspect/api'];

    public function __construct(
        private readonly ApiApplication $apiApplication,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        foreach (self::API_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $this->apiApplication->handle($request);
            }
        }

        return $handler->handle($request);
    }
}
