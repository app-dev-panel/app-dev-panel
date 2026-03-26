<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Storage;

use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskLog;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use DateTimeImmutable;
use PDO;

final class SqliteTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function save(Task $task): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO tasks (id, type, status, priority, payload, result, error, created_by, created_at, started_at, finished_at, scheduled_at, retry_count, max_retries, timeout)
            VALUES (:id, :type, :status, :priority, :payload, :result, :error, :created_by, :created_at, :started_at, :finished_at, :scheduled_at, :retry_count, :max_retries, :timeout)
            ON CONFLICT(id) DO UPDATE SET
                status = :status,
                result = :result,
                error = :error,
                started_at = :started_at,
                finished_at = :finished_at,
                retry_count = :retry_count
            SQL);

        $stmt->execute([
            'id' => $task->id,
            'type' => $task->type,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'payload' => json_encode($task->payload, JSON_THROW_ON_ERROR),
            'result' => $task->result !== null ? json_encode($task->result, JSON_THROW_ON_ERROR) : null,
            'error' => $task->error !== null ? json_encode($task->error, JSON_THROW_ON_ERROR) : null,
            'created_by' => $task->createdBy,
            'created_at' => $task->createdAt->format('Y-m-d H:i:s.u'),
            'started_at' => $task->startedAt?->format('Y-m-d H:i:s.u'),
            'finished_at' => $task->finishedAt?->format('Y-m-d H:i:s.u'),
            'scheduled_at' => $task->scheduledAt?->format('Y-m-d H:i:s.u'),
            'retry_count' => $task->retryCount,
            'max_retries' => $task->maxRetries,
            'timeout' => $task->timeoutSeconds,
        ]);
    }

    public function find(string $id): ?Task
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findByStatus(array $statuses, int $limit = 50, int $offset = 0): array
    {
        if ($statuses === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM tasks WHERE status IN ({$placeholders}) ORDER BY priority DESC, created_at ASC LIMIT ? OFFSET ?",
        );

        $params = array_map(static fn(TaskStatus $s): string => $s->value, $statuses);
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);

        return array_map($this->hydrate(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findPending(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT * FROM tasks
            WHERE status = 'pending'
              AND (scheduled_at IS NULL OR scheduled_at <= :now)
            ORDER BY priority DESC, created_at ASC
            LIMIT :limit
            SQL);
        $stmt->execute([
            'now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u'),
            'limit' => $limit,
        ]);

        return array_map($this->hydrate(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findScheduledReady(): array
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT * FROM tasks
            WHERE status = 'scheduled'
              AND scheduled_at IS NOT NULL
              AND scheduled_at <= :now
            ORDER BY priority DESC, scheduled_at ASC
            SQL);
        $stmt->execute([
            'now' => new DateTimeImmutable()->format('Y-m-d H:i:s.u'),
        ]);

        return array_map($this->hydrate(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function count(?TaskStatus $status = null): int
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE status = :status');
            $stmt->execute(['status' => $status->value]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
        }

        return (int) $stmt->fetchColumn();
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function addLog(string $taskId, string $level, string $message, ?array $context = null): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO task_logs (task_id, level, message, context, created_at)
            VALUES (:task_id, :level, :message, :context, :created_at)
            SQL);
        $stmt->execute([
            'task_id' => $taskId,
            'level' => $level,
            'message' => $message,
            'context' => $context !== null ? json_encode($context, JSON_THROW_ON_ERROR) : null,
            'created_at' => new DateTimeImmutable()->format('Y-m-d H:i:s.u'),
        ]);
    }

    public function getLogs(string $taskId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM task_logs WHERE task_id = :task_id ORDER BY id ASC');
        $stmt->execute(['task_id' => $taskId]);

        return array_map(
            static fn(array $row): TaskLog => new TaskLog(
                id: (int) $row['id'],
                taskId: $row['task_id'],
                level: $row['level'],
                message: $row['message'],
                context: $row['context'] !== null ? json_decode($row['context'], true, 512, JSON_THROW_ON_ERROR) : null,
                createdAt: new DateTimeImmutable($row['created_at']),
            ),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    private function hydrate(array $row): Task
    {
        return new Task(
            id: $row['id'],
            type: $row['type'],
            status: TaskStatus::from($row['status']),
            priority: TaskPriority::from((int) $row['priority']),
            payload: json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR),
            result: $row['result'] !== null ? json_decode($row['result'], true, 512, JSON_THROW_ON_ERROR) : null,
            error: $row['error'] !== null ? json_decode($row['error'], true, 512, JSON_THROW_ON_ERROR) : null,
            createdBy: $row['created_by'],
            createdAt: new DateTimeImmutable($row['created_at']),
            startedAt: $row['started_at'] !== null ? new DateTimeImmutable($row['started_at']) : null,
            finishedAt: $row['finished_at'] !== null ? new DateTimeImmutable($row['finished_at']) : null,
            scheduledAt: $row['scheduled_at'] !== null ? new DateTimeImmutable($row['scheduled_at']) : null,
            retryCount: (int) $row['retry_count'],
            maxRetries: (int) $row['max_retries'],
            timeoutSeconds: $row['timeout'] !== null ? (int) $row['timeout'] : null,
        );
    }
}
