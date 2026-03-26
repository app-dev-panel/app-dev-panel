<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit;

use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\TaskBus;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskBus::class)]
final class TaskBusTest extends TestCase
{
    private TaskBus $bus;

    protected function setUp(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(0, 'success', '', 0.1));

        $this->bus = TaskBusFactory::createInMemory(processRunner: $runner);
    }

    public function testRunCommand(): void
    {
        $taskId = $this->bus->runCommand('echo hello');

        $this->assertNotEmpty($taskId);
        $task = $this->bus->status($taskId);
        $this->assertNotNull($task);
        $this->assertSame('run_command', $task->type);
        // After sync dispatch, should be completed (handler ran immediately)
        $this->assertSame(TaskStatus::Completed, $task->status);
    }

    public function testRunTests(): void
    {
        $taskId = $this->bus->runTests('vendor/bin/phpunit', ['--testsuite', 'Kernel']);
        $task = $this->bus->status($taskId);

        $this->assertSame('run_tests', $task->type);
        $this->assertSame(TaskStatus::Completed, $task->status);
    }

    public function testRunAnalyzer(): void
    {
        $taskId = $this->bus->runAnalyzer('vendor/bin/mago', ['analyze']);
        $task = $this->bus->status($taskId);

        $this->assertSame('run_analyzer', $task->type);
        $this->assertSame(TaskStatus::Completed, $task->status);
    }

    public function testCancel(): void
    {
        // Schedule a future task (won't be executed immediately)
        $taskId = $this->bus->scheduleCommand('echo later', new DateTimeImmutable('+1 hour'));

        $this->assertTrue($this->bus->cancel($taskId));
        $this->assertSame(TaskStatus::Cancelled, $this->bus->status($taskId)->status);
    }

    public function testCancelTerminalTaskReturnsFalse(): void
    {
        $taskId = $this->bus->runCommand('echo done');
        // Already completed
        $this->assertFalse($this->bus->cancel($taskId));
    }

    public function testCancelNonexistentReturnsFalse(): void
    {
        $this->assertFalse($this->bus->cancel('nonexistent'));
    }

    public function testList(): void
    {
        $this->bus->runCommand('echo 1');
        $this->bus->runCommand('echo 2');

        $all = $this->bus->list();
        $this->assertCount(2, $all);
    }

    public function testListByStatus(): void
    {
        $this->bus->runCommand('echo done');
        $this->bus->scheduleCommand('echo later', new DateTimeImmutable('+1 hour'));

        $completed = $this->bus->list(TaskStatus::Completed);
        $this->assertCount(1, $completed);

        $scheduled = $this->bus->list(TaskStatus::Scheduled);
        $this->assertCount(1, $scheduled);
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->bus->count());

        $this->bus->runCommand('echo 1');
        $this->bus->runCommand('echo 2');

        $this->assertSame(2, $this->bus->count());
        $this->assertSame(2, $this->bus->count(TaskStatus::Completed));
        $this->assertSame(0, $this->bus->count(TaskStatus::Pending));
    }

    public function testLogs(): void
    {
        $taskId = $this->bus->runCommand('echo hello');
        $logs = $this->bus->logs($taskId);

        // Handler creates logs on execution
        $this->assertNotEmpty($logs);
    }

    public function testScheduleCommand(): void
    {
        $taskId = $this->bus->scheduleCommand('echo scheduled', new DateTimeImmutable('+30 minutes'));

        $task = $this->bus->status($taskId);
        $this->assertSame(TaskStatus::Scheduled, $task->status);
        $this->assertNotNull($task->scheduledAt);
    }

    public function testStatusReturnsNullForMissing(): void
    {
        $this->assertNull($this->bus->status('does-not-exist'));
    }
}
