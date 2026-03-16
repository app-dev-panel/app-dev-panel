<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Collects Doctrine DBAL query data.
 *
 * Integrates with Doctrine's SQL logger / middleware to capture:
 * - SQL queries with parameters
 * - Execution time per query
 * - Total query count and cumulative time
 *
 * Data is fed via `logQuery()` method, called from a Doctrine middleware or SQL logger.
 */
final class DoctrineCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{sql: string, params: array, types: array, executionTime: float, backtrace: array}> */
    private array $queries = [];
    private float $totalTime = 0.0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Called by the Doctrine middleware/logger for each executed query.
     */
    public function logQuery(string $sql, array $params = [], array $types = [], float $executionTime = 0.0): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
            'executionTime' => $executionTime,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ];
        $this->totalTime += $executionTime;

        $this->timelineCollector->collect($this, count($this->queries));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'queries' => $this->queries,
            'totalTime' => $this->totalTime,
            'queryCount' => count($this->queries),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'doctrine' => [
                'queryCount' => count($this->queries),
                'totalTime' => $this->totalTime,
            ],
        ];
    }

    private function reset(): void
    {
        $this->queries = [];
        $this->totalTime = 0.0;
    }
}
