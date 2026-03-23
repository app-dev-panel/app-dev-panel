<?php

declare(strict_types=1);

namespace App\bootstrap;

use yii\base\BootstrapInterface;

/**
 * Initializes the SQLite database with required tables for the playground.
 * Creates table on first request; ensures seed data is complete on every startup.
 */
final class DatabaseBootstrap implements BootstrapInterface
{
    private const SEED_DATA = [
        ['John Doe',   'john@example.com'],
        ['Jane Smith', 'jane@example.com'],
        ['Bob Wilson', 'bob@example.com'],
        ['Alice',      'alice@example.com'],
    ];

    public function bootstrap($app): void
    {
        if (!$app instanceof \yii\web\Application && !$app instanceof \yii\console\Application) {
            return;
        }

        $db = $app->db;
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_users') === null) {
            $db
                ->createCommand()
                ->createTable('test_users', [
                    'id' => 'pk',
                    'name' => 'string NOT NULL',
                    'email' => 'string NOT NULL',
                    'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
                ])
                ->execute();

            $db->createCommand()->batchInsert('test_users', ['name', 'email'], self::SEED_DATA)->execute();

            return;
        }

        // Ensure all seed rows exist (handles case where table was created with older seed data)
        $existingNames = $db->createCommand('SELECT name FROM test_users')->queryColumn();
        $missing = [];
        foreach (self::SEED_DATA as $row) {
            if (in_array($row[0], $existingNames, true)) {
                continue;
            }

            $missing[] = $row;
        }

        if ($missing !== []) {
            $db->createCommand()->batchInsert('test_users', ['name', 'email'], $missing)->execute();
        }
    }
}
