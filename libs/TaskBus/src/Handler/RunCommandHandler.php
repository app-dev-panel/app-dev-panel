<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Handler;

use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunCommandHandler
{
    public function __construct(
        private ProcessRunnerInterface $processRunner,
        private TaskRepositoryInterface $repository,
    ) {}

    public function __invoke(RunCommand $message): void
    {
        $task = $this->repository->find($message->taskId);
        if ($task === null || $task->isTerminal()) {
            return;
        }

        $task->markRunning();
        $this->repository->save($task);
        $this->repository->addLog($task->id, 'info', "Executing command: {$message->command}");

        $result = $this->processRunner->run(
            command: $message->command,
            workingDirectory: $message->workingDirectory,
            env: $message->env,
            timeout: $message->timeoutSeconds,
        );

        if ($result->isSuccessful()) {
            $task->markCompleted($result->toArray());
            $this->repository->addLog($task->id, 'info', 'Command completed successfully');
        } else {
            $task->markFailed($result->toArray());
            $this->repository->addLog($task->id, 'error', "Command failed with exit code {$result->exitCode}");
        }

        $this->repository->save($task);
    }
}
