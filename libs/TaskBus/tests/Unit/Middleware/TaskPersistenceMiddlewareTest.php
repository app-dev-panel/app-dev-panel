<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Middleware;

use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Middleware\TaskPersistenceMiddleware;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

#[CoversClass(TaskPersistenceMiddleware::class)]
final class TaskPersistenceMiddlewareTest extends TestCase
{
    public function testCreatesTaskOnDispatch(): void
    {
        $pdo = PdoFactory::createInMemory();
        $repository = new SqliteTaskRepository($pdo);
        $middleware = new TaskPersistenceMiddleware($repository);

        $message = new RunCommand(taskId: 'persist-1', command: 'echo hi');
        $envelope = new Envelope($message);

        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->method('handle')->willReturn($envelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($nextMiddleware);

        $middleware->handle($envelope, $stack);

        $task = $repository->find('persist-1');
        $this->assertNotNull($task);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame('run_command', $task->type);
        $this->assertSame(['command' => 'echo hi', 'working_directory' => null, 'env' => []], $task->payload);
    }

    public function testDoesNotOverwriteExistingTask(): void
    {
        $pdo = PdoFactory::createInMemory();
        $repository = new SqliteTaskRepository($pdo);
        $middleware = new TaskPersistenceMiddleware($repository);

        // Create task manually first
        $task = new \AppDevPanel\TaskBus\Task(
            id: 'persist-2',
            type: 'run_command',
            status: TaskStatus::Running,
            priority: \AppDevPanel\TaskBus\TaskPriority::Normal,
            payload: ['command' => 'echo original'],
        );
        $repository->save($task);

        $message = new RunCommand(taskId: 'persist-2', command: 'echo overwrite');
        $envelope = new Envelope($message);

        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->method('handle')->willReturn($envelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($nextMiddleware);

        $middleware->handle($envelope, $stack);

        $found = $repository->find('persist-2');
        $this->assertSame(TaskStatus::Running, $found->status);
    }
}
