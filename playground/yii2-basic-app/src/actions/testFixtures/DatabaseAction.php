<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class DatabaseAction extends Action
{
    public function run(): array
    {
        $db = \Yii::$app->db;

        // Execute real SQL queries via Yii's DB connection — the DbProfilingTarget
        // intercepts profiling messages from these calls and feeds query data
        // to DatabaseCollector.
        $db->createCommand(
            'CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)',
        )->execute();

        $db
            ->createCommand()
            ->insert('test_users', [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])
            ->execute();

        $result = $db->createCommand('SELECT * FROM test_users WHERE id = :id', ['id' => 1])->queryOne();

        return ['fixture' => 'database:basic', 'status' => 'ok', 'user' => $result];
    }
}
