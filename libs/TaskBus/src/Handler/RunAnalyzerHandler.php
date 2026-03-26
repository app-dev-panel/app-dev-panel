<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Handler;

use AppDevPanel\TaskBus\Message\RunAnalyzer;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunAnalyzerHandler
{
    public function __construct(
        private ProcessRunnerInterface $processRunner,
        private TaskRepositoryInterface $repository,
    ) {}

    public function __invoke(RunAnalyzer $message): void
    {
        $task = $this->repository->find($message->taskId);
        if ($task === null || $task->isTerminal()) {
            return;
        }

        $task->markRunning();
        $this->repository->save($task);

        $command = $message->analyzer;
        if ($message->args !== []) {
            $command .= ' ' . implode(' ', array_map('escapeshellarg', $message->args));
        }

        $this->repository->addLog($task->id, 'info', "Running analyzer: {$command}");

        $result = $this->processRunner->run(
            command: $command,
            workingDirectory: $message->workingDirectory,
            timeout: $message->timeoutSeconds,
        );

        if ($result->isSuccessful()) {
            $task->markCompleted($result->toArray());
            $this->repository->addLog($task->id, 'info', 'Analysis passed');
        } else {
            $task->markFailed($result->toArray());
            $this->repository->addLog($task->id, 'error', 'Analysis found issues');
        }

        $this->repository->save($task);
    }
}
