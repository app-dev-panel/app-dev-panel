<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures HTTP middleware stack execution data.
 *
 * Framework adapters call collectBefore() / collectAfter() with normalized middleware
 * data extracted from their middleware dispatcher events.
 */
final class MiddlewareCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{name: string, time: float, memory: int, request: mixed}> */
    private array $beforeStack = [];

    /** @var array<int, array{name: string, time: float, memory: int, response: mixed}> */
    private array $afterStack = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Record a middleware entering the stack (before processing).
     */
    public function collectBefore(string $name, float $time, int $memory, mixed $request = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->beforeStack[] = [
            'name' => $name,
            'time' => $time,
            'memory' => $memory,
            'request' => $request,
        ];
        $this->timelineCollector->collect($this, count($this->beforeStack));
    }

    /**
     * Record a middleware leaving the stack (after processing).
     */
    public function collectAfter(string $name, float $time, int $memory, mixed $response = null): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->afterStack[] = [
            'name' => $name,
            'time' => $time,
            'memory' => $memory,
            'response' => $response,
        ];
        $this->timelineCollector->collect($this, count($this->beforeStack) + count($this->afterStack));
    }

    public function getCollected(): array
    {
        $beforeStack = $this->beforeStack;
        $afterStack = $this->afterStack;
        $beforeAction = array_pop($beforeStack);
        $afterAction = array_shift($afterStack);
        $actionHandler = [];

        if (is_array($beforeAction) && is_array($afterAction)) {
            $actionHandler = [
                'name' => $beforeAction['name'],
                'startTime' => $beforeAction['time'],
                'request' => $beforeAction['request'],
                'response' => $afterAction['response'],
                'endTime' => $afterAction['time'],
                'memory' => $afterAction['memory'],
            ];
        }

        return [
            'beforeStack' => $beforeStack,
            'actionHandler' => $actionHandler,
            'afterStack' => $afterStack,
        ];
    }

    public function getSummary(): array
    {
        return [
            'middleware' => [
                'total' => ($total = count($this->beforeStack)) > 0 ? $total - 1 : 0,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->beforeStack = [];
        $this->afterStack = [];
    }
}
