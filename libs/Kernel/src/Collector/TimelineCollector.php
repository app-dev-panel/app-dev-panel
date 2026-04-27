<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class TimelineCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $events = [];

    public function getCollected(): array
    {
        return $this->events;
    }

    public function getSummary(): array
    {
        return [
            'timeline' => [
                'total' => count($this->events),
            ],
        ];
    }

    public function collect(CollectorInterface $collector, string|int $reference, mixed ...$data): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->events[] = [microtime(true), $reference, $collector::class, array_values($data)];
    }

    private function reset(): void
    {
        $this->events = [];
    }
}
