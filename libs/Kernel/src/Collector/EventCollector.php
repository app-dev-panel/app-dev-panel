<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use ReflectionClass;

final class EventCollector implements SummaryCollectorInterface, HtmlViewProviderInterface
{
    use CollectorTrait;

    private array $events = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        return $this->events;
    }

    public function collect(object $event, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->events[] = [
            'name' => $event::class,
            'event' => $event,
            'file' => new ReflectionClass($event)->getFileName(),
            'line' => $line,
            'time' => microtime(true),
        ];
        $this->timelineCollector->collect($this, spl_object_id($event), $event::class);
    }

    public function getSummary(): array
    {
        return [
            'event' => [
                'total' => count($this->events),
            ],
        ];
    }

    private function reset(): void
    {
        $this->events = [];
    }

    public static function getViewPath(): string
    {
        return dirname(__DIR__, 2) . '/views/event-collector.php';
    }
}
