<?php

declare(strict_types=1);

namespace App\Application;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Tiny PSR-18 client used by the `http-client` fixture endpoint.
 *
 * Avoids introducing Guzzle as a playground dependency: returns a canned JSON response
 * so the {@see \AppDevPanel\Kernel\Collector\HttpClientCollector} can observe the call.
 */
final class LoopbackHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $factory = new Psr17Factory();
        $body = json_encode([
            'loopback' => true,
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
        ], JSON_THROW_ON_ERROR);

        return $factory
            ->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream($body));
    }
}
