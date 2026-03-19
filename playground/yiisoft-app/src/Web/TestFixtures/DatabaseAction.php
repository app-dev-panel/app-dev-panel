<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class DatabaseAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private DatabaseCollector $databaseCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Simulate a database query by calling the collector directly.
        // This tests the DatabaseCollector without requiring yiisoft/db infrastructure.
        $start = microtime(true);
        $this->databaseCollector->logQuery(
            sql: 'SELECT * FROM test WHERE id = :id',
            rawSql: 'SELECT * FROM test WHERE id = 1',
            params: [':id' => 1],
            line: __FILE__ . ':' . __LINE__,
            startTime: $start,
            endTime: microtime(true),
            rowsNumber: 1,
        );

        return $this->responseFactory->createResponse(['fixture' => 'database:basic', 'status' => 'ok']);
    }
}
