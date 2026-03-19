<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Collects Symfony Messenger data.
 *
 * Captures:
 * - Dispatched messages (class name, bus, transport)
 * - Handler execution results
 * - Failed messages with exception info
 *
 * Data is fed from Symfony Messenger middleware.
 */
final class MessengerCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{messageClass: string, bus: string, transport: ?string, dispatched: bool, handled: bool, failed: bool, duration: float}> */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function logMessage(
        string $messageClass,
        string $bus = 'default',
        ?string $transport = null,
        bool $dispatched = true,
        bool $handled = false,
        bool $failed = false,
        float $duration = 0.0,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'messageClass' => $messageClass,
            'bus' => $bus,
            'transport' => $transport,
            'dispatched' => $dispatched,
            'handled' => $handled,
            'failed' => $failed,
            'duration' => $duration,
        ];

        $this->timelineCollector->collect($this, count($this->messages));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messages' => $this->messages,
            'messageCount' => count($this->messages),
            'failedCount' => count(array_filter($this->messages, static fn(array $m) => $m['failed'])),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'messenger' => [
                'messageCount' => count($this->messages),
                'failedCount' => count(array_filter($this->messages, static fn(array $m) => $m['failed'])),
            ],
        ];
    }

    private function reset(): void
    {
        $this->messages = [];
    }
}
