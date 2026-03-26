<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Message;

use AppDevPanel\TaskBus\TaskPriority;

abstract class AbstractTaskMessage
{
    public function __construct(
        public readonly string $taskId,
        public readonly TaskPriority $priority = TaskPriority::Normal,
        public readonly ?int $timeoutSeconds = null,
        public readonly string $createdBy = 'user',
    ) {}

    abstract public function getType(): string;

    abstract public function getPayload(): array;
}
