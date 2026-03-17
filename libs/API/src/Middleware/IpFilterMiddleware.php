<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IpFilterMiddleware implements MiddlewareInterface
{
    /**
     * @param string[] $allowedIps
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly array $allowedIps = [],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->allowedIps === []) {
            return $handler->handle($request);
        }

        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '';

        if (!in_array($remoteAddr, $this->allowedIps, true)) {
            $body = json_encode([
                'error' => 'Access denied.',
                'success' => false,
            ], JSON_THROW_ON_ERROR);

            return $this->responseFactory
                ->createResponse(403)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($body));
        }

        return $handler->handle($request);
    }
}
