<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use function count;
use function is_countable;

/**
 * Captures message queue and message bus operations.
 *
 * Unified collector for both queue systems (Yiisoft Queue) and message buses
 * (Symfony Messenger, etc.). Framework adapters call:
 * - collectPush(), collectStatus(), collectWorkerProcessing() for queue operations
 * - logMessage() for message bus dispatches
 */
final class QueueCollector implements SummaryCollectorInterface
{
    use CollectorTrait;
    use DuplicateDetectionTrait;

    /** @var array<string, array<int, array{message: mixed, middlewares: array}>> */
    private array $pushes = [];

    /** @var array<int, array{id: string, status: string}> */
    private array $statuses = [];

    /** @var array<string, array<int, mixed>> */
    private array $processingMessages = [];

    /** @var array<int, array{messageClass: string, bus: string, transport: ?string, dispatched: bool, handled: bool, failed: bool, duration: float, message: mixed}> */
    private array $messages = [];

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

    public function logMessage(MessageRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = $record->toArray();

        $this->timelineCollector->collect($this, count($this->messages));
    }

    public function getCollected(): array
    {
        return [
            'pushes' => $this->pushes,
            'statuses' => $this->statuses,
            'processingMessages' => $this->processingMessages,
            'messages' => $this->messages,
            'messageCount' => count($this->messages),
            'failedCount' => count(array_filter($this->messages, static fn(array $m) => $m['failed'])),
            'duplicates' => $this->detectDuplicates(
                $this->messages,
                static fn(array $message) => $message['messageClass'],
            ),
        ];
    }

    public function getSummary(): array
    {
        $duplicates = $this->detectDuplicates($this->messages, static fn(array $message) => $message['messageClass']);

        return [
            'queue' => [
                'countPushes' => array_sum(array_map(static fn($m) => is_countable($m) ? count($m) : 0, $this->pushes)),
                'countStatuses' => count($this->statuses),
                'countProcessingMessages' => array_sum(array_map(static fn($m) => is_countable($m)
                    ? count($m)
                    : 0, $this->processingMessages)),
                'messageCount' => count($this->messages),
                'failedCount' => count(array_filter($this->messages, static fn(array $m) => $m['failed'])),
                'duplicateGroups' => count($duplicates['groups']),
                'totalDuplicatedCount' => $duplicates['totalDuplicatedCount'],
            ],
        ];
    }

    protected function reset(): void
    {
        $this->pushes = [];
        $this->statuses = [];
        $this->processingMessages = [];
        $this->messages = [];
    }
}
