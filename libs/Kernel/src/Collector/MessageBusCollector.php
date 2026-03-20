<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures message bus / message queue dispatch data.
 *
 * Framework adapters call logMessage() with normalized data.
 * Works with any message bus (Symfony Messenger, Laravel Queues, etc.).
 */
final class MessageBusCollector implements SummaryCollectorInterface
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
            'messageBus' => [
                'messageCount' => count($this->messages),
                'failedCount' => count(array_filter($this->messages, static fn(array $m) => $m['failed'])),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->messages = [];
    }
}
