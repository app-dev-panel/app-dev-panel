<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Handler;

use AppDevPanel\TaskBus\Message\AgentTask;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AgentTaskHandler
{
    /**
     * @param array<string, non-empty-string> $actionCommands Mapping of action names to shell commands
     */
    public function __construct(
        private ProcessRunnerInterface $processRunner,
        private TaskRepositoryInterface $repository,
        private array $actionCommands = [],
    ) {}

    public function __invoke(AgentTask $message): void
    {
        $task = $this->repository->find($message->taskId);
        if ($task === null || $task->isTerminal()) {
            return;
        }

        $task->markRunning();
        $this->repository->save($task);

        $command = $this->actionCommands[$message->action] ?? null;
        if ($command === null) {
            $task->markFailed(['error' => "Unknown agent action: {$message->action}"]);
            $this->repository->addLog($task->id, 'error', "Unknown action: {$message->action}");
            $this->repository->save($task);
            return;
        }

        $this->repository->addLog($task->id, 'info', "Executing agent action: {$message->action}");

        $result = $this->processRunner->run(command: $command, timeout: $message->timeoutSeconds);

        if ($result->isSuccessful()) {
            $task->markCompleted($result->toArray());
            $this->repository->addLog($task->id, 'info', "Agent action '{$message->action}' completed");
        } else {
            $task->markFailed($result->toArray());
            $this->repository->addLog($task->id, 'error', "Agent action '{$message->action}' failed");
        }

        $this->repository->save($task);
    }
}
