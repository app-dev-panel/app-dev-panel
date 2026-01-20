<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

final class TimelineCollector implements CollectorInterface
{
    use CollectorTrait;

    private array $events = [];

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->events;
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
