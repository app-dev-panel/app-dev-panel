<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Middleware;

use AppDevPanel\TaskBus\Message\AbstractTaskMessage;
use AppDevPanel\TaskBus\Storage\TaskRepositoryInterface;
use AppDevPanel\TaskBus\TaskStatus;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class TaskRetryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TaskRepositoryInterface $repository,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (HandlerFailedException $e) {
            $message = $envelope->getMessage();
            if (!$message instanceof AbstractTaskMessage) {
                throw $e;
            }

            $task = $this->repository->find($message->taskId);
            if ($task === null) {
                throw $e;
            }

            $task->incrementRetry();

            if ($task->canRetry()) {
                $task->status = TaskStatus::Pending;
                $this->repository->addLog(
                    $task->id,
                    'warning',
                    "Retry {$task->retryCount}/{$task->maxRetries}: " . $e->getMessage(),
                );
            } else {
                $task->markFailed([
                    'error' => $e->getMessage(),
                    'retries_exhausted' => true,
                ]);
                $this->repository->addLog($task->id, 'error', 'All retries exhausted');
            }

            $this->repository->save($task);
            throw $e;
        }
    }
}
