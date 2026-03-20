<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use yii\base\Action;

final class DatabaseAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var DatabaseCollector|null $databaseCollector */
        $databaseCollector = $module->getCollector(DatabaseCollector::class);

        if ($databaseCollector === null) {
            return ['fixture' => 'database:basic', 'status' => 'error', 'message' => 'DatabaseCollector not found'];
        }

        // Simulate a database query by calling the collector directly.
        // This tests the DatabaseCollector without requiring actual DB queries.
        $start = microtime(true);
        $databaseCollector->logQuery(
            sql: 'SELECT * FROM test WHERE id = :id',
            rawSql: 'SELECT * FROM test WHERE id = 1',
            params: [':id' => 1],
            line: __FILE__ . ':' . __LINE__,
            startTime: $start,
            endTime: microtime(true),
            rowsNumber: 1,
        );

        return ['fixture' => 'database:basic', 'status' => 'ok'];
    }
}
