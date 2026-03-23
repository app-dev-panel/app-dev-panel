<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures cache operations (get, set, delete) across any caching backend.
 *
 * Framework adapters call logCacheOperation() with normalized data.
 * Tracks hit/miss rates per pool and operation timing.
 */
final class CacheCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{pool: string, operation: string, key: string, hit: bool, duration: float, value: mixed}> */
    private array $operations = [];
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function logCacheOperation(CacheOperationRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->operations[] = $record->toArray();

        if ($record->operation === 'get') {
            if ($record->hit) {
                ++$this->hits;
            } else {
                ++$this->misses;
            }
        }

        $this->timelineCollector->collect($this, count($this->operations));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'operations' => $this->operations,
            'hits' => $this->hits,
            'misses' => $this->misses,
            'totalOperations' => count($this->operations),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'cache' => [
                'hits' => $this->hits,
                'misses' => $this->misses,
                'totalOperations' => count($this->operations),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->operations = [];
        $this->hits = 0;
        $this->misses = 0;
    }
}
