<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class TokenAuthMiddleware implements MiddlewareInterface
{
    private bool $insecureWarningLogged = false;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        #[\SensitiveParameter]
        private readonly string $token,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->token === '') {
            if (!$this->insecureWarningLogged) {
                $this->logger->warning(
                    'ADP TokenAuthMiddleware is installed with an empty token — all requests to the debug API bypass authentication. Configure a non-empty token in production.',
                );
                $this->insecureWarningLogged = true;
            }

            return $handler->handle($request);
        }

        $header = $request->getHeaderLine('X-Debug-Token');

        if (hash_equals($this->token, $header)) {
            return $handler->handle($request);
        }

        $body = json_encode([
            'error' => 'Invalid or missing authentication token.',
            'success' => false,
        ], JSON_THROW_ON_ERROR);

        return $this->responseFactory
            ->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }
}
