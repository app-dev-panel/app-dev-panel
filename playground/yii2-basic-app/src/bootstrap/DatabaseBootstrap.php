<?php

declare(strict_types=1);

namespace App\bootstrap;

use yii\base\BootstrapInterface;

/**
 * Initializes the SQLite database with required tables for the playground.
 * Runs once on first request; subsequent requests skip if tables exist.
 */
final class DatabaseBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app instanceof \yii\web\Application && !$app instanceof \yii\console\Application) {
            return;
        }

        $db = $app->db;

        $schema = $db->getSchema();
        if ($schema->getTableSchema('test_users') !== null) {
            return;
        }

        $db
            ->createCommand()
            ->createTable('test_users', [
                'id' => 'pk',
                'name' => 'string NOT NULL',
                'email' => 'string NOT NULL',
                'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            ])
            ->execute();

        $db
            ->createCommand()
            ->batchInsert(
                'test_users',
                ['name', 'email'],
                [
                    ['John Doe',   'john@example.com'],
                    ['Jane Smith', 'jane@example.com'],
                    ['Bob Wilson', 'bob@example.com'],
                ],
            )
            ->execute();
    }
}
