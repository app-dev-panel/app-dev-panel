<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ElasticsearchController
{
    private const int MAX_LIMIT = 1000;
    private const int MIN_LIMIT = 1;

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly ElasticsearchProviderInterface $provider,
    ) {}

    public function health(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'health' => $this->provider->getHealth(),
            'indices' => $this->provider->getIndices(),
        ]);
    }

    public function getIndex(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $name */
        $name = $request->getAttribute('name');

        return $this->responseFactory->createJsonResponse($this->provider->getIndex($name));
    }

    public function search(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{index?: string, query?: array, limit?: int, offset?: int} $body */
        $body = json_decode((string) $request->getBody(), true);
        $index = $body['index'] ?? '';
        $query = $body['query'] ?? [];
        $limit = max(self::MIN_LIMIT, min(
            (int) ($body['limit'] ?? ElasticsearchProviderInterface::DEFAULT_LIMIT),
            self::MAX_LIMIT,
        ));
        $offset = max((int) ($body['offset'] ?? 0), 0);

        if ($index === '') {
            return $this->responseFactory->createJsonResponse(['error' => 'Index name is required'], 400);
        }

        try {
            $result = $this->provider->search($index, $query, $limit, $offset);

            return $this->responseFactory->createJsonResponse($result);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function query(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{method?: string, endpoint?: string, body?: array} $body */
        $body = json_decode((string) $request->getBody(), true);
        $method = $body['method'] ?? '';
        $endpoint = $body['endpoint'] ?? '';

        if ($method === '' || $endpoint === '') {
            return $this->responseFactory->createJsonResponse(['error' => 'Method and endpoint are required'], 400);
        }

        try {
            $result = $this->provider->executeQuery($method, $endpoint, $body['body'] ?? []);

            return $this->responseFactory->createJsonResponse($result);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
