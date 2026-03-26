<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Handler;

use AppDevPanel\TaskBus\Handler\RunTestsHandler;
use AppDevPanel\TaskBus\Message\RunTests;
use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RunTestsHandler::class)]
final class RunTestsHandlerTest extends TestCase
{
    private SqliteTaskRepository $repository;

    protected function setUp(): void
    {
        $pdo = PdoFactory::createInMemory();
        $this->repository = new SqliteTaskRepository($pdo);
    }

    public function testPassingTests(): void
    {
        $this->repository->save($this->createTask('t1'));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner
            ->method('run')
            ->with("vendor/bin/phpunit '--testsuite' 'Kernel'", null, [], 600)
            ->willReturn(new ProcessResult(0, 'OK (10 tests)', '', 5.0));

        $handler = new RunTestsHandler($runner, $this->repository);
        $handler(new RunTests(taskId: 't1', runner: 'vendor/bin/phpunit', args: ['--testsuite', 'Kernel']));

        $task = $this->repository->find('t1');
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertTrue($task->result['success']);
    }

    public function testFailingTests(): void
    {
        $this->repository->save($this->createTask('t2'));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(1, '', 'FAILURES!', 3.0));

        $handler = new RunTestsHandler($runner, $this->repository);
        $handler(new RunTests(taskId: 't2', runner: 'vendor/bin/phpunit'));

        $task = $this->repository->find('t2');
        $this->assertSame(TaskStatus::Failed, $task->status);
    }

    public function testNoArgsCommand(): void
    {
        $this->repository->save($this->createTask('t3'));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner
            ->method('run')
            ->with('vendor/bin/phpunit', null, [], 600)
            ->willReturn(new ProcessResult(0, 'OK', '', 1.0));

        $handler = new RunTestsHandler($runner, $this->repository);
        $handler(new RunTests(taskId: 't3', runner: 'vendor/bin/phpunit'));

        $this->assertSame(TaskStatus::Completed, $this->repository->find('t3')->status);
    }

    private function createTask(string $id): Task
    {
        return new Task(
            id: $id,
            type: 'run_tests',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: [],
        );
    }
}
