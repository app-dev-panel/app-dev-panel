<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Message;

use AppDevPanel\TaskBus\TaskPriority;

final class RunAnalyzer extends AbstractTaskMessage
{
    /**
     * @param non-empty-string $analyzer Analyzer binary (e.g. 'vendor/bin/mago', 'vendor/bin/phpstan')
     * @param list<string> $args Additional arguments (e.g. ['analyze', '--no-progress'])
     * @param non-empty-string|null $workingDirectory
     */
    public function __construct(
        string $taskId,
        public readonly string $analyzer,
        public readonly array $args = [],
        public readonly ?string $workingDirectory = null,
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeoutSeconds = 300,
        string $createdBy = 'user',
    ) {
        parent::__construct($taskId, $priority, $timeoutSeconds, $createdBy);
    }

    public function getType(): string
    {
        return 'run_analyzer';
    }

    public function getPayload(): array
    {
        return [
            'analyzer' => $this->analyzer,
            'args' => $this->args,
            'working_directory' => $this->workingDirectory,
        ];
    }
}
