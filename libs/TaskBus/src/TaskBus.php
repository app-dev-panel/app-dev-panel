<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

use AppDevPanel\TaskBus\Message\AbstractTaskMessage;
use AppDevPanel\TaskBus\Message\AgentTask;
use AppDevPanel\TaskBus\Message\RunAnalyzer;
use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Message\RunTests;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class TaskBus
{
    public function __construct(
        private MessageBusInterface $bus,
        private TaskRepositoryInterface $repository,
        private TaskBusConfig $config,
    ) {}

    public function dispatch(AbstractTaskMessage $message): string
    {
        $this->bus->dispatch($message);

        return $message->taskId;
    }

    public function runCommand(
        string $command,
        ?string $workingDirectory = null,
        array $env = [],
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeout = null,
        string $createdBy = 'user',
    ): string {
        return $this->dispatch(new RunCommand(
            taskId: $this->generateId(),
            command: $command,
            workingDirectory: $workingDirectory,
            env: $env,
            priority: $priority,
            timeoutSeconds: $timeout ?? $this->config->defaultTimeout,
            createdBy: $createdBy,
        ));
    }

    public function runTests(
        string $runner,
        array $args = [],
        ?string $workingDirectory = null,
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeout = null,
    ): string {
        return $this->dispatch(new RunTests(
            taskId: $this->generateId(),
            runner: $runner,
            args: $args,
            workingDirectory: $workingDirectory,
            priority: $priority,
            timeoutSeconds: $timeout ?? $this->config->defaultTimeout,
        ));
    }

    public function runAnalyzer(
        string $analyzer,
        array $args = [],
        ?string $workingDirectory = null,
        TaskPriority $priority = TaskPriority::Normal,
        ?int $timeout = null,
    ): string {
        return $this->dispatch(new RunAnalyzer(
            taskId: $this->generateId(),
            analyzer: $analyzer,
            args: $args,
            workingDirectory: $workingDirectory,
            priority: $priority,
            timeoutSeconds: $timeout ?? $this->config->defaultTimeout,
        ));
    }

    public function submitAgentTask(
        string $action,
        array $parameters = [],
        TaskPriority $priority = TaskPriority::High,
        ?int $timeout = null,
    ): string {
        return $this->dispatch(new AgentTask(
            taskId: $this->generateId(),
            action: $action,
            parameters: $parameters,
            priority: $priority,
            timeoutSeconds: $timeout ?? $this->config->defaultTimeout,
        ));
    }

    public function scheduleCommand(
        string $command,
        DateTimeImmutable $scheduledAt,
        ?string $workingDirectory = null,
        TaskPriority $priority = TaskPriority::Normal,
    ): string {
        $taskId = $this->generateId();
        $task = new Task(
            id: $taskId,
            type: 'run_command',
            status: TaskStatus::Scheduled,
            priority: $priority,
            payload: ['command' => $command, 'working_directory' => $workingDirectory],
            scheduledAt: $scheduledAt,
        );
        $this->repository->save($task);

        return $taskId;
    }

    public function cancel(string $taskId): bool
    {
        $task = $this->repository->find($taskId);
        if ($task === null || $task->isTerminal()) {
            return false;
        }

        $task->markCancelled();
        $this->repository->save($task);
        $this->repository->addLog($taskId, 'info', 'Task cancelled');

        return true;
    }

    public function status(string $taskId): ?Task
    {
        return $this->repository->find($taskId);
    }

    /**
     * @return list<Task>
     */
    public function list(?TaskStatus $status = null, int $limit = 50, int $offset = 0): array
    {
        if ($status !== null) {
            return $this->repository->findByStatus([$status], $limit, $offset);
        }

        return $this->repository->findByStatus(TaskStatus::cases(), $limit, $offset);
    }

    /**
     * @return list<TaskLog>
     */
    public function logs(string $taskId): array
    {
        return $this->repository->getLogs($taskId);
    }

    public function count(?TaskStatus $status = null): int
    {
        return $this->repository->count($status);
    }

    private function generateId(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
