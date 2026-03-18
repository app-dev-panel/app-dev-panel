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

    /** @var array<int, array{sql: string, params: array, rowCount: int, time: float, type: string, backtrace: string}> */
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

        $time = 0.0;
        if ($this->queryStartTime !== null) {
            $time = microtime(true) - $this->queryStartTime;
            $this->queryStartTime = null;
        }

        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $backtrace = '';
        foreach ($callStack as $frame) {
            if (isset($frame['file']) && !str_contains($frame['file'], '/vendor/')) {
                $backtrace = $frame['file'] . ':' . ($frame['line'] ?? 0);
                break;
            }
        }

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'rowCount' => $rowCount,
            'time' => $time,
            'type' => self::detectSqlType($sql),
            'backtrace' => $backtrace,
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
            'queryCount' => count($this->queries),
            'connectionCount' => $this->connectionCount,
            'totalTime' => $this->totalTime,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'db' => [
                'queryCount' => count($this->queries),
                'totalTime' => round($this->totalTime * 1000, 2),
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

    private static function detectSqlType(string $sql): string
    {
        $normalized = ltrim($sql);
        $firstWord = strtoupper(strtok($normalized, " \t\n\r"));

        return match ($firstWord) {
            'SELECT' => 'SELECT',
            'INSERT' => 'INSERT',
            'UPDATE' => 'UPDATE',
            'DELETE' => 'DELETE',
            'CREATE' => 'CREATE',
            'ALTER' => 'ALTER',
            'DROP' => 'DROP',
            'TRUNCATE' => 'TRUNCATE',
            'BEGIN', 'START' => 'TRANSACTION',
            'COMMIT' => 'COMMIT',
            'ROLLBACK' => 'ROLLBACK',
            'SHOW' => 'SHOW',
            'DESCRIBE', 'DESC', 'EXPLAIN' => 'EXPLAIN',
            default => 'OTHER',
        };
    }
}
