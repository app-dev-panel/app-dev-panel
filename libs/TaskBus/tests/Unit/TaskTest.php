<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit;

use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Task::class)]
final class TaskTest extends TestCase
{
    public function testConstructDefaults(): void
    {
        $task = new Task(
            id: 'test-1',
            type: 'run_command',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: ['command' => 'echo hi'],
        );

        $this->assertSame('test-1', $task->id);
        $this->assertSame('run_command', $task->type);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame(TaskPriority::Normal, $task->priority);
        $this->assertSame(['command' => 'echo hi'], $task->payload);
        $this->assertNull($task->result);
        $this->assertNull($task->error);
        $this->assertSame('user', $task->createdBy);
        $this->assertNull($task->startedAt);
        $this->assertNull($task->finishedAt);
        $this->assertSame(0, $task->retryCount);
        $this->assertSame(3, $task->maxRetries);
        $this->assertFalse($task->isTerminal());
    }

    public function testMarkRunning(): void
    {
        $task = $this->createTask();
        $task->markRunning();

        $this->assertSame(TaskStatus::Running, $task->status);
        $this->assertNotNull($task->startedAt);
    }

    public function testMarkCompleted(): void
    {
        $task = $this->createTask();
        $task->markCompleted(['exit_code' => 0, 'stdout' => 'ok']);

        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertSame(['exit_code' => 0, 'stdout' => 'ok'], $task->result);
        $this->assertNotNull($task->finishedAt);
        $this->assertTrue($task->isTerminal());
    }

    public function testMarkFailed(): void
    {
        $task = $this->createTask();
        $task->markFailed(['error' => 'segfault']);

        $this->assertSame(TaskStatus::Failed, $task->status);
        $this->assertSame(['error' => 'segfault'], $task->error);
        $this->assertTrue($task->isTerminal());
    }

    public function testMarkCancelled(): void
    {
        $task = $this->createTask();
        $task->markCancelled();

        $this->assertSame(TaskStatus::Cancelled, $task->status);
        $this->assertTrue($task->isTerminal());
    }

    public function testRetryLogic(): void
    {
        $task = new Task(
            id: 'retry-test',
            type: 'test',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: [],
            maxRetries: 2,
        );

        $this->assertTrue($task->canRetry());
        $task->incrementRetry();
        $this->assertSame(1, $task->retryCount);
        $this->assertTrue($task->canRetry());

        $task->incrementRetry();
        $this->assertSame(2, $task->retryCount);
        $this->assertFalse($task->canRetry());
    }

    private function createTask(): Task
    {
        return new Task(
            id: 'test-1',
            type: 'run_command',
            status: TaskStatus::Pending,
            priority: TaskPriority::Normal,
            payload: ['command' => 'echo hi'],
        );
    }
}
