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

    /** @var array<int, array{sql: string, rawSql: string, params: array, line: string, status: string, actions: array}> */
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

        $endTime = microtime(true);
        $startTime = $endTime - $executionTime;

        $this->queries[] = [
            'sql' => $sql,
            'rawSql' => $sql,
            'params' => $this->normalizeParams($params),
            'line' => $this->extractCallerLine(),
            'status' => 'success',
            'actions' => [
                ['action' => 'query.start', 'time' => $startTime],
                ['action' => 'query.end', 'time' => $endTime],
            ],
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
            'transactions' => [],
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

    private function extractCallerLine(): string
    {
        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($callStack as $frame) {
            if (
                isset($frame['file'])
                && !str_contains($frame['file'], '/vendor/')
                && !str_contains($frame['file'], '/Collector/DoctrineCollector.php')
            ) {
                return $frame['file'] . ':' . ($frame['line'] ?? 0);
            }
        }
        return '';
    }

    /**
     * @param array<mixed> $params
     * @return array<string, int|string>
     */
    private function normalizeParams(array $params): array
    {
        $result = [];
        foreach ($params as $key => $value) {
            $result[(string) $key] = is_scalar($value) ? $value : (string) $value;
        }
        return $result;
    }
}
