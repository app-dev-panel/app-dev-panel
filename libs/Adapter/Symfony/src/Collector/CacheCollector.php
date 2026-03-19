<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Collects Symfony Cache component data.
 *
 * Captures:
 * - Cache hits/misses per pool
 * - Cache operation timing
 * - Total reads, writes, deletes
 *
 * Data is fed via `logCacheOperation()`, called from a decorated cache adapter.
 */
final class CacheCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{pool: string, operation: string, key: string, hit: bool, duration: float}> */
    private array $operations = [];
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function logCacheOperation(
        string $pool,
        string $operation,
        string $key,
        bool $hit = false,
        float $duration = 0.0,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->operations[] = [
            'pool' => $pool,
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration' => $duration,
        ];

        if ($operation === 'get') {
            if ($hit) {
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

    private function reset(): void
    {
        $this->operations = [];
        $this->hits = 0;
        $this->misses = 0;
    }
}
