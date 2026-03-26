<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Message;

use AppDevPanel\TaskBus\TaskPriority;

final class RunTests extends AbstractTaskMessage
{
    /**
     * @param non-empty-string $runner Test runner binary (e.g. 'vendor/bin/phpunit', 'npx vitest')
     * @param list<string> $args Additional arguments (e.g. ['--testsuite', 'Kernel'])
     * @param non-empty-string|null $workingDirectory
     */
    public function __construct(
        string $taskId,
        public readonly string $runner,
        public readonly array $args = [],
        public readonly ?string $workingDirectory = null,
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeoutSeconds = 600,
        string $createdBy = 'user',
    ) {
        parent::__construct($taskId, $priority, $timeoutSeconds, $createdBy);
    }

    public function getType(): string
    {
        return 'run_tests';
    }

    public function getPayload(): array
    {
        return [
            'runner' => $this->runner,
            'args' => $this->args,
            'working_directory' => $this->workingDirectory,
        ];
    }
}
