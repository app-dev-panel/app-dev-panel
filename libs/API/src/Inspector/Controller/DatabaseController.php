<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DatabaseController
{
    private const int MAX_LIMIT = 1000;
    private const int MIN_LIMIT = 1;

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly SchemaProviderInterface $schemaProvider,
    ) {}

    public function getTables(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse($this->schemaProvider->getTables());
    }

    public function getTable(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $tableName */
        $tableName = $request->getAttribute('name');
        $queryParams = $request->getQueryParams();
        $limit = max(self::MIN_LIMIT, min((int) ($queryParams['limit'] ?? SchemaProviderInterface::DEFAULT_LIMIT), self::MAX_LIMIT));
        $offset = max((int) ($queryParams['offset'] ?? 0), 0);

        return $this->responseFactory->createJsonResponse($this->schemaProvider->getTable($tableName, $limit, $offset));
    }

    public function explain(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{sql?: string, params?: array<string, mixed>, analyze?: bool} $body */
        $body = json_decode((string) $request->getBody(), true);
        $sql = $body['sql'] ?? '';
        $params = $body['params'] ?? [];
        $analyze = $body['analyze'] ?? false;

        if ($sql === '') {
            return $this->responseFactory->createJsonResponse(['error' => 'SQL query is required'], 400);
        }

        try {
            $result = $this->schemaProvider->explainQuery($sql, $params, $analyze);

            return $this->responseFactory->createJsonResponse($result);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function query(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{sql?: string, params?: array<string, mixed>} $body */
        $body = json_decode((string) $request->getBody(), true);
        $sql = $body['sql'] ?? '';
        $params = $body['params'] ?? [];

        if ($sql === '') {
            return $this->responseFactory->createJsonResponse(['error' => 'SQL query is required'], 400);
        }

        try {
            $result = $this->schemaProvider->executeQuery($sql, $params);

            return $this->responseFactory->createJsonResponse($result);
        } catch (\Throwable $e) {
            return $this->responseFactory->createJsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
