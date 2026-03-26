<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Cancelled => true,
            default => false,
        };
    }
}
