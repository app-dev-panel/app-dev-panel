<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Captures SQL queries executed via Yii 2's DB layer.
 *
 * Fed by event hooks on yii\db\Command::EVENT_AFTER_EXECUTE,
 * registered in the Module's registerDbProfiling() method.
 */
final class DbCollector implements CollectorInterface, SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{sql: string, params: array, rowCount: int, time: float, backtrace: string}> */
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

    public function logQuery(string $sql, array $params = [], int $rowCount = 0): void
    {
        if (!$this->isActive()) {
            return;
        }

        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $backtrace = '';
        foreach ($callStack as $frame) {
            if (isset($frame['file']) && !str_contains($frame['file'], '/vendor/')) {
                $backtrace = $frame['file'] . ':' . ($frame['line'] ?? 0);
                break;
            }
        }

        // Estimate timing from profiling data if available
        $time = 0.0;

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'rowCount' => $rowCount,
            'time' => $time,
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
}
