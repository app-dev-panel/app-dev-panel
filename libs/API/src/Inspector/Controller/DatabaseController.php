<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DatabaseController
{
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
        $tableName = $request->getAttribute('name');
        $queryParams = $request->getQueryParams();
        $limit = min((int) ($queryParams['limit'] ?? 1000), 10000);
        $offset = max((int) ($queryParams['offset'] ?? 0), 0);

        return $this->responseFactory->createJsonResponse($this->schemaProvider->getTable($tableName, $limit, $offset));
    }
}
