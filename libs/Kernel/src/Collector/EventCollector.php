<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use ReflectionClass;

final class EventCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $events = [];

    /**
     * List of event class names that should be collected even before the collector is active.
     * The Adapter should configure this with its application startup event classes.
     *
     * @var string[]
     */
    private array $earlyAcceptedEvents = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * @param string[] $eventClasses Event class names to accept before collector is active.
     */
    public function withEarlyAcceptedEvents(array $eventClasses): self
    {
        $new = clone $this;
        $new->earlyAcceptedEvents = $eventClasses;
        return $new;
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->events;
    }

    public function collect(object $event, string $line): void
    {
        if (!in_array($event::class, $this->earlyAcceptedEvents, true) && !$this->isActive()) {
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
        if (!$this->isActive()) {
            return [];
        }
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
}
