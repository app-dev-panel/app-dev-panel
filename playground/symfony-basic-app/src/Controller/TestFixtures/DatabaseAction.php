<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/database', name: 'test_database', methods: ['GET'])]
final readonly class DatabaseAction
{
    public function __construct(
        private DatabaseCollector $databaseCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Simulate a database query by calling the collector directly.
        // This tests the DatabaseCollector without requiring Doctrine DBAL infrastructure.
        $start = microtime(true);
        $this->databaseCollector->logQuery(
            sql: 'SELECT * FROM test_users WHERE id = :id',
            rawSql: 'SELECT * FROM test_users WHERE id = 1',
            params: ['id' => 1],
            line: __FILE__ . ':' . __LINE__,
            startTime: $start,
            endTime: microtime(true),
            rowsNumber: 1,
        );

        return new JsonResponse(['fixture' => 'database:basic', 'status' => 'ok']);
    }
}
