<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

final readonly class TaskBusConfig
{
    /**
     * @param non-empty-string $databasePath Path to SQLite database file
     * @param positive-int $defaultTimeout Default task timeout in seconds
     * @param positive-int $maxConcurrentTasks Maximum number of concurrent running tasks
     * @param positive-int $workerSleepInterval Worker sleep interval in microseconds between polls
     * @param list<non-empty-string> $allowedCommands Whitelist of allowed shell commands (empty = allow all)
     */
    public function __construct(
        public string $databasePath = 'task-bus.sqlite',
        public int $defaultTimeout = 300,
        public int $maxConcurrentTasks = 4,
        public int $workerSleepInterval = 200_000,
        public array $allowedCommands = [],
    ) {}
}
