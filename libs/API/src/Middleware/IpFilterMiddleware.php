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
     * @param string[] $allowedIps     Allowed client IPs. Empty list means
     *                                 "allow everyone" unless `$strict` is
     *                                 enabled.
     * @param string[] $trustedProxies IPs of trusted reverse proxies. When
     *                                 the immediate `REMOTE_ADDR` matches one
     *                                 of them, the client IP is extracted
     *                                 from the `X-Forwarded-For` chain
     *                                 instead. Without this list the header
     *                                 is ignored so attackers behind a load
     *                                 balancer cannot spoof their IP.
     * @param bool     $strict         When true, an empty `$allowedIps` list
     *                                 rejects every request (deny-by-default)
     *                                 instead of passing through.
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly array $allowedIps = [],
        private readonly array $trustedProxies = [],
        private readonly bool $strict = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->allowedIps === [] && !$this->strict) {
            return $handler->handle($request);
        }

        $remoteAddr = $this->resolveClientIp($request);

        if ($this->allowedIps === [] || !in_array($remoteAddr, $this->allowedIps, true)) {
            return $this->deny();
        }

        return $handler->handle($request);
    }

    private function resolveClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = (string) ($serverParams['REMOTE_ADDR'] ?? '');

        if ($this->trustedProxies === [] || !in_array($remoteAddr, $this->trustedProxies, true)) {
            return $remoteAddr;
        }

        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded === '') {
            return $remoteAddr;
        }

        // XFF is a comma-separated list `client, proxy1, proxy2`. Walk from
        // the right, skipping trusted proxies, to find the first untrusted
        // hop — that is the actual client.
        $chain = array_map('trim', explode(',', $forwarded));
        while ($chain !== []) {
            $candidate = array_pop($chain);
            if (!in_array($candidate, $this->trustedProxies, true)) {
                return $candidate;
            }
        }

        return $remoteAddr;
    }

    private function deny(): ResponseInterface
    {
        $body = json_encode([
            'error' => 'Access denied.',
            'success' => false,
        ], JSON_THROW_ON_ERROR);

        return $this->responseFactory
            ->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }
}
