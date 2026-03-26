<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Storage;

use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqliteTaskRepository::class)]
final class SqliteTaskRepositoryTest extends TestCase
{
    private SqliteTaskRepository $repository;

    protected function setUp(): void
    {
        $pdo = PdoFactory::createInMemory();
        $this->repository = new SqliteTaskRepository($pdo);
    }

    public function testSaveAndFind(): void
    {
        $task = $this->createTask('task-1');
        $this->repository->save($task);

        $found = $this->repository->find('task-1');
        $this->assertNotNull($found);
        $this->assertSame('task-1', $found->id);
        $this->assertSame('run_command', $found->type);
        $this->assertSame(TaskStatus::Pending, $found->status);
        $this->assertSame(['command' => 'echo hello'], $found->payload);
    }

    public function testFindReturnsNullForMissing(): void
    {
        $this->assertNull($this->repository->find('nonexistent'));
    }

    public function testSaveUpdatesExistingTask(): void
    {
        $task = $this->createTask('task-1');
        $this->repository->save($task);

        $task->markRunning();
        $this->repository->save($task);

        $found = $this->repository->find('task-1');
        $this->assertSame(TaskStatus::Running, $found->status);
        $this->assertNotNull($found->startedAt);
    }

    public function testFindByStatus(): void
    {
        $this->repository->save($this->createTask('t1', TaskStatus::Pending));
        $this->repository->save($this->createTask('t2', TaskStatus::Running));
        $this->repository->save($this->createTask('t3', TaskStatus::Completed));
        $this->repository->save($this->createTask('t4', TaskStatus::Pending));

        $pending = $this->repository->findByStatus([TaskStatus::Pending]);
        $this->assertCount(2, $pending);

        $running = $this->repository->findByStatus([TaskStatus::Running]);
        $this->assertCount(1, $running);

        $mixed = $this->repository->findByStatus([TaskStatus::Pending, TaskStatus::Running]);
        $this->assertCount(3, $mixed);
    }

    public function testFindByStatusEmptyReturnsEmpty(): void
    {
        $this->assertSame([], $this->repository->findByStatus([]));
    }

    public function testFindPending(): void
    {
        $this->repository->save($this->createTask('t1', TaskStatus::Pending));
        $this->repository->save($this->createTask('t2', TaskStatus::Running));

        $futureTask = new Task(
            id: 't3',
            type: 'run_command',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: [],
            scheduledAt: new DateTimeImmutable('+1 hour'),
        );
        $this->repository->save($futureTask);

        $pending = $this->repository->findPending();
        $this->assertCount(1, $pending);
        $this->assertSame('t1', $pending[0]->id);
    }

    public function testFindScheduledReady(): void
    {
        $readyTask = new Task(
            id: 'ready',
            type: 'run_command',
            status: TaskStatus::Scheduled,
            priority: TaskPriority::Normal,
            payload: [],
            scheduledAt: new DateTimeImmutable('-1 minute'),
        );
        $this->repository->save($readyTask);

        $futureTask = new Task(
            id: 'future',
            type: 'run_command',
            status: TaskStatus::Scheduled,
            priority: TaskPriority::Normal,
            payload: [],
            scheduledAt: new DateTimeImmutable('+1 hour'),
        );
        $this->repository->save($futureTask);

        $ready = $this->repository->findScheduledReady();
        $this->assertCount(1, $ready);
        $this->assertSame('ready', $ready[0]->id);
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->repository->count());

        $this->repository->save($this->createTask('t1', TaskStatus::Pending));
        $this->repository->save($this->createTask('t2', TaskStatus::Completed));

        $this->assertSame(2, $this->repository->count());
        $this->assertSame(1, $this->repository->count(TaskStatus::Pending));
        $this->assertSame(1, $this->repository->count(TaskStatus::Completed));
        $this->assertSame(0, $this->repository->count(TaskStatus::Failed));
    }

    public function testDelete(): void
    {
        $this->repository->save($this->createTask('t1'));
        $this->assertNotNull($this->repository->find('t1'));

        $this->repository->delete('t1');
        $this->assertNull($this->repository->find('t1'));
    }

    public function testLogs(): void
    {
        $this->repository->save($this->createTask('t1'));

        $this->repository->addLog('t1', 'info', 'Starting task');
        $this->repository->addLog('t1', 'error', 'Something failed', ['code' => 42]);

        $logs = $this->repository->getLogs('t1');
        $this->assertCount(2, $logs);
        $this->assertSame('info', $logs[0]->level);
        $this->assertSame('Starting task', $logs[0]->message);
        $this->assertSame('error', $logs[1]->level);
        $this->assertSame(['code' => 42], $logs[1]->context);
    }

    public function testPriorityOrdering(): void
    {
        $this->repository->save($this->createTask('low', TaskStatus::Pending, TaskPriority::Low));
        $this->repository->save($this->createTask('high', TaskStatus::Pending, TaskPriority::High));
        $this->repository->save($this->createTask('normal', TaskStatus::Pending, TaskPriority::Normal));

        $pending = $this->repository->findPending();
        $this->assertSame('high', $pending[0]->id);
        $this->assertSame('normal', $pending[1]->id);
        $this->assertSame('low', $pending[2]->id);
    }

    private function createTask(
        string $id,
        TaskStatus $status = TaskStatus::Pending,
        TaskPriority $priority = TaskPriority::Normal,
    ): Task {
        return new Task(id: $id, type: 'run_command', status: $status, priority: $priority, payload: [
            'command' => 'echo hello',
        ]);
    }
}
