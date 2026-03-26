<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Handler;

use AppDevPanel\TaskBus\Handler\RunCommandHandler;
use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RunCommandHandler::class)]
final class RunCommandHandlerTest extends TestCase
{
    private SqliteTaskRepository $repository;

    protected function setUp(): void
    {
        $pdo = PdoFactory::createInMemory();
        $this->repository = new SqliteTaskRepository($pdo);
    }

    public function testSuccessfulCommand(): void
    {
        $task = new Task(
            id: 'cmd-1',
            type: 'run_command',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: ['command' => 'echo hello'],
        );
        $this->repository->save($task);

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(exitCode: 0, stdout: 'hello', stderr: '', duration: 0.1));

        $handler = new RunCommandHandler($runner, $this->repository);
        $handler(new RunCommand(taskId: 'cmd-1', command: 'echo hello'));

        $updated = $this->repository->find('cmd-1');
        $this->assertSame(TaskStatus::Completed, $updated->status);
        $this->assertSame(0, $updated->result['exit_code']);
        $this->assertSame('hello', $updated->result['stdout']);
        $this->assertNotNull($updated->startedAt);
        $this->assertNotNull($updated->finishedAt);
    }

    public function testFailedCommand(): void
    {
        $task = new Task(
            id: 'cmd-2',
            type: 'run_command',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: ['command' => 'false'],
        );
        $this->repository->save($task);

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(exitCode: 1, stdout: '', stderr: 'error', duration: 0.05));

        $handler = new RunCommandHandler($runner, $this->repository);
        $handler(new RunCommand(taskId: 'cmd-2', command: 'false'));

        $updated = $this->repository->find('cmd-2');
        $this->assertSame(TaskStatus::Failed, $updated->status);
        $this->assertSame(1, $updated->error['exit_code']);

        $logs = $this->repository->getLogs('cmd-2');
        $this->assertNotEmpty($logs);
    }

    public function testSkipsTerminalTask(): void
    {
        $task = new Task(
            id: 'cmd-3',
            type: 'run_command',
            status: TaskStatus::Cancelled,
            priority: TaskPriority::Normal,
            payload: [],
        );
        $this->repository->save($task);

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())->method('run');

        $handler = new RunCommandHandler($runner, $this->repository);
        $handler(new RunCommand(taskId: 'cmd-3', command: 'echo nope'));
    }

    public function testSkipsMissingTask(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())->method('run');

        $handler = new RunCommandHandler($runner, $this->repository);
        $handler(new RunCommand(taskId: 'nonexistent', command: 'echo nope'));
    }
}
