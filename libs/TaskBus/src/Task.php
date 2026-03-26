<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

use DateTimeImmutable;

final class Task
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public TaskStatus $status,
        public readonly TaskPriority $priority,
        public readonly array $payload,
        public ?array $result = null,
        public ?array $error = null,
        public readonly string $createdBy = 'user',
        public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $startedAt = null,
        public ?DateTimeImmutable $finishedAt = null,
        public ?DateTimeImmutable $scheduledAt = null,
        public int $retryCount = 0,
        public readonly int $maxRetries = 3,
        public readonly ?int $timeoutSeconds = null,
    ) {}

    public function markRunning(): void
    {
        $this->status = TaskStatus::Running;
        $this->startedAt = new DateTimeImmutable();
    }

    public function markCompleted(array $result): void
    {
        $this->status = TaskStatus::Completed;
        $this->result = $result;
        $this->finishedAt = new DateTimeImmutable();
    }

    public function markFailed(array $error): void
    {
        $this->status = TaskStatus::Failed;
        $this->error = $error;
        $this->finishedAt = new DateTimeImmutable();
    }

    public function markCancelled(): void
    {
        $this->status = TaskStatus::Cancelled;
        $this->finishedAt = new DateTimeImmutable();
    }

    public function incrementRetry(): void
    {
        $this->retryCount++;
    }

    public function canRetry(): bool
    {
        return $this->retryCount < $this->maxRetries;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
