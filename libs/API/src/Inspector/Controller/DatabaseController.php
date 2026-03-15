<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

final class DatabaseController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function getTables(SchemaProviderInterface $schemaProvider): ResponseInterface
    {
        return $this->responseFactory->createResponse($schemaProvider->getTables());
    }

    public function getTable(
        SchemaProviderInterface $schemaProvider,
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $tableName = $currentRoute->getArgument('name');
        $queryParams = $request->getQueryParams();
        $limit = min((int) ($queryParams['limit'] ?? 1000), 10000);
        $offset = max((int) ($queryParams['offset'] ?? 0), 0);

        return $this->responseFactory->createResponse($schemaProvider->getTable($tableName, $limit, $offset));
    }
}
