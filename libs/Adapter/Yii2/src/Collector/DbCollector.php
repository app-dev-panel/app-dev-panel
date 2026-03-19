<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Captures SQL queries executed via Yii 2's DB layer with accurate timing.
 *
 * Uses paired EVENT_BEFORE_EXECUTE / EVENT_AFTER_EXECUTE hooks to measure
 * actual query execution time. Also captures SQL type, parameters, row count,
 * and caller backtrace.
 */
final class DbCollector implements CollectorInterface, SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{sql: string, rawSql: string, params: array, line: string, status: string, actions: array, rowsNumber: int}> */
    private array $queries = [];
    private int $connectionCount = 0;
    private float $totalTime = 0.0;
    private ?float $queryStartTime = null;

    public function __construct(
        private readonly TimelineCollector $timeline,
    ) {}

    public function logConnection(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->connectionCount++;
    }

    /**
     * Called before query execution to start the timer.
     */
    public function beginQuery(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->queryStartTime = microtime(true);
    }

    /**
     * Called after query execution to record the query with timing.
     */
    public function logQuery(string $sql, array $params = [], int $rowCount = 0): void
    {
        if (!$this->isActive()) {
            return;
        }

        $endTime = microtime(true);
        $startTime = $this->queryStartTime ?? $endTime;
        $time = $endTime - $startTime;
        $this->queryStartTime = null;

        $this->queries[] = [
            'sql' => $sql,
            'rawSql' => $sql,
            'params' => $params,
            'line' => $this->extractCallerLine(),
            'status' => 'success',
            'actions' => [
                ['action' => 'query.start', 'time' => $startTime],
                ['action' => 'query.end', 'time' => $endTime],
            ],
            'rowsNumber' => $rowCount,
        ];

        $this->totalTime += $time;

        $this->timeline->collect($this, count($this->queries));
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
            'db' => [
                'queries' => [
                    'error' => 0,
                    'total' => count($this->queries),
                ],
                'transactions' => [
                    'error' => 0,
                    'total' => 0,
                ],
            ],
        ];
    }

    protected function reset(): void
    {
        $this->queries = [];
        $this->connectionCount = 0;
        $this->totalTime = 0.0;
        $this->queryStartTime = null;
    }

    private function extractCallerLine(): string
    {
        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($callStack as $frame) {
            if (
                isset($frame['file'])
                && !str_contains($frame['file'], '/vendor/')
                && !str_contains($frame['file'], '/Collector/DbCollector.php')
            ) {
                return $frame['file'] . ':' . ($frame['line'] ?? 0);
            }
        }
        return '';
    }
}
