<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Middleware;

use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts Laravel/Symfony HttpFoundation objects to PSR-7.
 */
final class Psr7Converter
{
    public function convertRequest(Request $request): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $psrRequest = $psr17Factory->createServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->server->all(),
        );

        foreach ($request->headers->all() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        $psrRequest = $psrRequest->withQueryParams($request->query->all());

        $content = $request->getContent();
        if ($content !== '' && $content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrRequest = $psrRequest->withBody($body);
        }

        return $psrRequest;
    }

    public function convertResponse(Response $response): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrResponse = $psr17Factory->createResponse($response->getStatusCode());

        foreach ($response->headers->all() as $name => $values) {
            $psrResponse = $psrResponse->withHeader($name, $values);
        }

        $content = $response->getContent();
        if ($content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrResponse = $psrResponse->withBody($body);
        }

        return $psrResponse;
    }
}
