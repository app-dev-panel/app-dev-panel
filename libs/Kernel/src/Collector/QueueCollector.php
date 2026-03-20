<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use function count;
use function is_countable;

/**
 * Captures message queue operations: pushes, statuses, processing.
 *
 * Framework adapters call collectPush(), collectStatus(), collectWorkerProcessing()
 * with normalized data from their queue system.
 */
final class QueueCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<string, array<int, array{message: mixed, middlewares: array}>> */
    private array $pushes = [];

    /** @var array<int, array{id: string, status: string}> */
    private array $statuses = [];

    /** @var array<string, array<int, mixed>> */
    private array $processingMessages = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collectStatus(string $id, string $status): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->statuses[] = [
            'id' => $id,
            'status' => $status,
        ];
        $this->timelineCollector->collect($this, count($this->statuses));
    }

    /**
     * @param array $middlewareDefinitions Middleware definitions applied during push
     */
    public function collectPush(string $queueName, mixed $message, array $middlewareDefinitions = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->pushes[$queueName][] = [
            'message' => $message,
            'middlewares' => $middlewareDefinitions,
        ];
        $this->timelineCollector->collect($this, count($this->pushes));
    }

    public function collectWorkerProcessing(mixed $message, string $queueName): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->processingMessages[$queueName][] = $message;
        $this->timelineCollector->collect($this, count($this->processingMessages));
    }

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

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'queue' => [
                'countPushes' => array_sum(array_map(static fn($m) => is_countable($m) ? count($m) : 0, $this->pushes)),
                'countStatuses' => count($this->statuses),
                'countProcessingMessages' => array_sum(array_map(static fn($m) => is_countable($m)
                    ? count($m)
                    : 0, $this->processingMessages)),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->pushes = [];
        $this->statuses = [];
        $this->processingMessages = [];
    }
}
