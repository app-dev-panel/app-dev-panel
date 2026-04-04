<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\HttpMock\HttpMockProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpMockController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly HttpMockProviderInterface $provider,
    ) {}

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->provider->getStatus());
    }

    public function listExpectations(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->provider->listExpectations());
    }

    public function createExpectation(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->provider->createExpectation($body);

        return $this->responseFactory->createJsonResponse(['success' => true]);
    }

    public function clearExpectations(ServerRequestInterface $request): ResponseInterface
    {
        $this->provider->clearExpectations();

        return $this->responseFactory->createJsonResponse(['success' => true]);
    }

    public function verify(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $count = $this->provider->verifyRequest($body);

        return $this->responseFactory->createJsonResponse(['count' => $count]);
    }

    public function history(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->provider->getRequestHistory());
    }

    public function reset(ServerRequestInterface $request): ResponseInterface
    {
        $this->provider->reset();

        return $this->responseFactory->createJsonResponse(['success' => true]);
    }
}
