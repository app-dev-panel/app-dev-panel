<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Storage;

use PDO;

final class SqliteSchema
{
    public static function create(PDO $pdo): void
    {
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
        $pdo->exec('PRAGMA foreign_keys=ON');

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tasks (
                id          TEXT PRIMARY KEY,
                type        TEXT NOT NULL,
                status      TEXT NOT NULL DEFAULT 'pending',
                priority    INTEGER NOT NULL DEFAULT 0,
                payload     TEXT NOT NULL DEFAULT '{}',
                result      TEXT,
                error       TEXT,
                created_by  TEXT NOT NULL DEFAULT 'user',
                created_at  TEXT NOT NULL,
                started_at  TEXT,
                finished_at TEXT,
                scheduled_at TEXT,
                retry_count INTEGER NOT NULL DEFAULT 0,
                max_retries INTEGER NOT NULL DEFAULT 3,
                timeout     INTEGER
            )
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS task_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id     TEXT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
                level       TEXT NOT NULL,
                message     TEXT NOT NULL,
                context     TEXT,
                created_at  TEXT NOT NULL
            )
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS schedules (
                id          TEXT PRIMARY KEY,
                name        TEXT NOT NULL UNIQUE,
                cron        TEXT NOT NULL,
                message_type TEXT NOT NULL,
                message_payload TEXT NOT NULL DEFAULT '{}',
                enabled     INTEGER NOT NULL DEFAULT 1,
                last_run_at TEXT,
                next_run_at TEXT
            )
            SQL);

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)');
        $pdo->exec(
            'CREATE INDEX IF NOT EXISTS idx_tasks_scheduled ON tasks(scheduled_at) WHERE scheduled_at IS NOT NULL',
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_priority ON tasks(priority DESC, created_at ASC)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_task_logs_task ON task_logs(task_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_schedules_next ON schedules(next_run_at) WHERE enabled = 1');
    }
}
