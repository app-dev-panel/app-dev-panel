<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts Symfony HttpFoundation objects to PSR-7.
 */
final class Psr7Converter
{
    private ?Psr17Factory $psr17Factory = null;

    public function convertRequest(Request $symfonyRequest): ServerRequestInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $psrRequest = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
        )->fromGlobals();

        $uri = $psr17Factory->createUri($symfonyRequest->getUri());
        $psrRequest = $psrRequest->withUri($uri)->withMethod($symfonyRequest->getMethod());

        foreach ($symfonyRequest->headers->all() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        return $psrRequest;
    }

    public function convertResponse(Response $symfonyResponse): ResponseInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $psrResponse = $psr17Factory->createResponse($symfonyResponse->getStatusCode());

        foreach ($symfonyResponse->headers->all() as $name => $values) {
            $psrResponse = $psrResponse->withHeader($name, $values);
        }

        $content = $symfonyResponse->getContent();
        if ($content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrResponse = $psrResponse->withBody($body);
        }

        return $psrResponse;
    }

    private function getPsr17Factory(): Psr17Factory
    {
        return $this->psr17Factory ??= new Psr17Factory();
    }
}
