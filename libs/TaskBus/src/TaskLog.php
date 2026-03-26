<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

use DateTimeImmutable;

final readonly class TaskLog
{
    public function __construct(
        public int $id,
        public string $taskId,
        public string $level,
        public string $message,
        public ?array $context = null,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}
}
