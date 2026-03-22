<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Controller;

use AppDevPanel\Api\ApiApplication;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Catch-all controller that bridges Laravel HTTP into the framework-agnostic ADP ApiApplication.
 *
 * Converts Laravel Request → PSR-7, delegates to ApiApplication, converts PSR-7 Response → Symfony Response.
 */
final class AdpApiController
{
    public function __construct(
        private readonly ApiApplication $apiApplication,
    ) {}

    public function __invoke(Request $request): Response
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

        $psrResponse = $this->apiApplication->handle($psrRequest);

        $contentType = $psrResponse->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'text/event-stream')) {
            return $this->createStreamedResponse($psrResponse);
        }

        $symfonyResponse = new Response((string) $psrResponse->getBody(), $psrResponse->getStatusCode());

        foreach ($psrResponse->getHeaders() as $name => $values) {
            $symfonyResponse->headers->set($name, $values);
        }

        return $symfonyResponse;
    }

    private function createStreamedResponse(\Psr\Http\Message\ResponseInterface $psrResponse): StreamedResponse
    {
        $body = $psrResponse->getBody();
        $headers = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[$name] = $values;
        }

        return new StreamedResponse(
            static function () use ($body): void {
                while (!$body->eof()) {
                    echo $body->read(8192);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            },
            $psrResponse->getStatusCode(),
            $headers,
        );
    }
}
