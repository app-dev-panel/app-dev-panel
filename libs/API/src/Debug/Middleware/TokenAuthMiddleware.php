<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Json\Json;

final class TokenAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $token,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->token === '') {
            return $handler->handle($request);
        }

        $header = $request->getHeaderLine('X-Debug-Token');

        if ($header === $this->token) {
            return $handler->handle($request);
        }

        $body = Json::encode([
            'error' => 'Invalid or missing authentication token.',
            'success' => false,
        ]);

        return $this->responseFactory
            ->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }
}
