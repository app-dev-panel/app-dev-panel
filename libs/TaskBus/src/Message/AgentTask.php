<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Message;

use AppDevPanel\TaskBus\TaskPriority;

final class AgentTask extends AbstractTaskMessage
{
    /**
     * @param non-empty-string $action Action identifier (e.g. 'fix_code', 'generate_tests', 'refactor')
     * @param array<string, mixed> $parameters Task-specific parameters
     */
    public function __construct(
        string $taskId,
        public readonly string $action,
        public readonly array $parameters = [],
        TaskPriority $priority = TaskPriority::High,
        ?int $timeoutSeconds = 600,
    ) {
        parent::__construct($taskId, $priority, $timeoutSeconds, 'agent');
    }

    public function getType(): string
    {
        return 'agent_task';
    }

    public function getPayload(): array
    {
        return [
            'action' => $this->action,
            'parameters' => $this->parameters,
        ];
    }
}
