<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Middleware;

use AppDevPanel\TaskBus\Message\AbstractTaskMessage;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use AppDevPanel\TaskBus\Task;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class TaskPersistenceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TaskRepositoryInterface $repository,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof AbstractTaskMessage) {
            $existingTask = $this->repository->find($message->taskId);
            if ($existingTask === null) {
                $task = new Task(
                    id: $message->taskId,
                    type: $message->getType(),
                    status: \AppDevPanel\TaskBus\TaskStatus::Pending,
                    priority: $message->priority,
                    payload: $message->getPayload(),
                    createdBy: $message->createdBy,
                    timeoutSeconds: $message->timeoutSeconds,
                );
                $this->repository->save($task);
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
