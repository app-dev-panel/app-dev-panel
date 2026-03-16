<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Queue;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

use function count;

final class QueueCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $pushes = [];
    private array $statuses = [];
    private array $processingMessages = [];

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'pushes' => $this->pushes,
            'statuses' => $this->statuses,
            'processingMessages' => $this->processingMessages,
        ];
    }

    public function collectStatus(string $id, JobStatus $status): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->statuses[] = [
            'id' => $id,
            'status' => $status->key(),
        ];
    }

    public function collectPush(
        string $queueName,
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->pushes[$queueName][] = [
            'message' => $message,
            'middlewares' => $middlewareDefinitions,
        ];
    }

    public function collectWorkerProcessing(MessageInterface $message, QueueInterface $queue): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->processingMessages[$queue->getName()][] = $message;
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'countPushes' => array_sum(array_map(static fn($m) => is_countable($m) ? count($m) : 0, $this->pushes)),
            'countStatuses' => count($this->statuses),
            'countProcessingMessages' => array_sum(array_map(static fn($m) => is_countable($m)
                ? count($m)
                : 0, $this->processingMessages)),
        ];
    }

    private function reset(): void
    {
        $this->pushes = [];
        $this->statuses = [];
        $this->processingMessages = [];
    }
}
