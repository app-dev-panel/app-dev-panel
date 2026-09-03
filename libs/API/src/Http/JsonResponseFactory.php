<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class JsonResponseFactory implements JsonResponseFactoryInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function createJsonResponse(mixed $data, int $status = 200): ResponseInterface
    {
        // INVALID_UTF8_SUBSTITUTE: collected payloads may carry binary/latin-1 bytes
        // (request bodies, exception messages); replace them with U+FFFD instead of
        // failing the whole response.
        $json = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );

        return $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($json));
    }
}
