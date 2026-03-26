<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Handler;

use AppDevPanel\TaskBus\Handler\AgentTaskHandler;
use AppDevPanel\TaskBus\Message\AgentTask;
use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AgentTaskHandler::class)]
final class AgentTaskHandlerTest extends TestCase
{
    private SqliteTaskRepository $repository;

    protected function setUp(): void
    {
        $pdo = PdoFactory::createInMemory();
        $this->repository = new SqliteTaskRepository($pdo);
    }

    public function testKnownAction(): void
    {
        $this->repository->save($this->createTask('a1'));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner
            ->method('run')
            ->with('vendor/bin/mago fmt', null, [], 600)
            ->willReturn(new ProcessResult(0, 'Fixed 3 files', '', 1.0));

        $handler = new AgentTaskHandler($runner, $this->repository, [
            'fix_code' => 'vendor/bin/mago fmt',
        ]);
        $handler(new AgentTask(taskId: 'a1', action: 'fix_code'));

        $task = $this->repository->find('a1');
        $this->assertSame(TaskStatus::Completed, $task->status);
    }

    public function testUnknownAction(): void
    {
        $this->repository->save($this->createTask('a2'));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())->method('run');

        $handler = new AgentTaskHandler($runner, $this->repository, []);
        $handler(new AgentTask(taskId: 'a2', action: 'unknown_action'));

        $task = $this->repository->find('a2');
        $this->assertSame(TaskStatus::Failed, $task->status);
        $this->assertStringContainsString('Unknown agent action', $task->error['error']);
    }

    private function createTask(string $id): Task
    {
        return new Task(
            id: $id,
            type: 'agent_task',
            status: TaskStatus::Pending,
            priority: TaskPriority::High,
            payload: [],
            createdBy: 'agent',
        );
    }
}
