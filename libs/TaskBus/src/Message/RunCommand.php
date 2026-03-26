<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Message;

use AppDevPanel\TaskBus\TaskPriority;

final class RunCommand extends AbstractTaskMessage
{
    /**
     * @param non-empty-string $command Shell command to execute
     * @param non-empty-string|null $workingDirectory Working directory for the command
     * @param array<string, string> $env Environment variables
     */
    public function __construct(
        string $taskId,
        public readonly string $command,
        public readonly ?string $workingDirectory = null,
        public readonly array $env = [],
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeoutSeconds = 300,
        string $createdBy = 'user',
    ) {
        parent::__construct($taskId, $priority, $timeoutSeconds, $createdBy);
    }

    public function getType(): string
    {
        return 'run_command';
    }

    public function getPayload(): array
    {
        return [
            'command' => $this->command,
            'working_directory' => $this->workingDirectory,
            'env' => $this->env,
        ];
    }
}
