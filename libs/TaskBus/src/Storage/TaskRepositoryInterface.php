<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Storage;

use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskLog;
use AppDevPanel\TaskBus\TaskStatus;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;

    public function find(string $id): ?Task;

    /**
     * @param list<TaskStatus> $statuses
     * @return list<Task>
     */
    public function findByStatus(array $statuses, int $limit = 50, int $offset = 0): array;

    /**
     * @return list<Task>
     */
    public function findPending(int $limit = 10): array;

    /**
     * @return list<Task>
     */
    public function findScheduledReady(): array;

    public function count(?TaskStatus $status = null): int;

    public function delete(string $id): void;

    public function addLog(string $taskId, string $level, string $message, ?array $context = null): void;

    /**
     * @return list<TaskLog>
     */
    public function getLogs(string $taskId): array;
}
