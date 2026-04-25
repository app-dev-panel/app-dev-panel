<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Api;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Streams panel + toolbar bundles from the `app-dev-panel/frontend-assets`
 * package over `/debug-assets/{path}`. Path safety, MIME mapping, and
 * cache-control selection live in {@see FrontendAssets}.
 */
final class AdpAssetMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $prefix = FrontendAssets::URL_PREFIX;

        if ($path !== $prefix && !str_starts_with($path, $prefix . '/')) {
            return $handler->handle($request);
        }

        $relative = substr($path, \strlen($prefix) + 1);
        $resolved = FrontendAssets::resolve($relative);
        if ($resolved === null) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->responseFactory
            ->createResponse(200)
            ->withBody($this->streamFactory->createStreamFromFile($resolved))
            ->withHeader('Content-Type', FrontendAssets::mimeFor($resolved))
            ->withHeader('Cache-Control', FrontendAssets::cacheControlFor($resolved));
    }
}
