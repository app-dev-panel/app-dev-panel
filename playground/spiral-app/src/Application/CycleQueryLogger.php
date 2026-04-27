<?php

declare(strict_types=1);

namespace App\Application;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Bridges cycle/database query logs into the ADP {@see DatabaseCollector}.
 *
 * cycle/database 2.x calls `LoggerInterface::info(<sql>, ['driver' => ..., 'elapsed' => float, 'rowCount' => int])`
 * for every successful query and `error()` / `alert()` for failures. We turn each
 * info-level entry into a {@see QueryRecord} with the driver-reported elapsed time.
 */
final class CycleQueryLogger extends AbstractLogger implements LoggerFactoryInterface
{
    public function __construct(
        private readonly DatabaseCollector $collector,
    ) {}

    public function getLogger(?DriverInterface $driver = null): LoggerInterface
    {
        // The same instance handles every driver — DatabaseCollector aggregates them all.
        return $this;
    }

    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        // Only record successful query traces (info). Errors/alerts go through the
        // ExceptionCollector via the thrown PDOException; we don't double-record them.
        if ((string) $level !== 'info') {
            return;
        }

        $sql = (string) $message;
        $elapsed = (float) ($context['elapsed'] ?? 0.0);
        $rows = (int) ($context['rowCount'] ?? 0);
        $end = microtime(true);

        $this->collector->logQuery(new QueryRecord(
            sql: $sql,
            rawSql: $sql,
            params: [],
            line: '',
            startTime: $end - $elapsed,
            endTime: $end,
            rowsNumber: $rows,
        ));
    }
}
