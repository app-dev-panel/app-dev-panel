<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use AppDevPanel\Kernel\Event\MethodCallRecord;

final class ServiceCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $items = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->items;
    }

    public function collect(MethodCallRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->items[] = [
            'service' => $record->service,
            'class' => $record->class,
            'method' => $record->methodName,
            'arguments' => $record->arguments,
            'result' => $record->result,
            'status' => $record->status,
            'error' => $record->error,
            'timeStart' => $record->timeStart,
            'timeEnd' => $record->timeEnd,
        ];
        $this->timelineCollector->collect($this, count($this->items));
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'service' => [
                'total' => count($this->items),
            ],
        ];
    }

    private function reset(): void
    {
        $this->items = [];
    }
}
