<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Illuminate\Http\JsonResponse;

final readonly class DatabaseAction
{
    public function __construct(
        private DatabaseCollector $databaseCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
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
