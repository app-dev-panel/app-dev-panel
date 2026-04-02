<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;

/**
 * Listens for database query events and feeds the DatabaseCollector.
 *
 * Captures both successful queries (via QueryExecuted event) and failed queries
 * (via collectFailedQuery called from DebugMiddleware on QueryException).
 */
final class DatabaseListener
{
    /** @var \Closure(): DatabaseCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): DatabaseCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $collector = ($this->collectorFactory)();

            $endTime = microtime(true);
            $startTime = $endTime - ($event->time / 1000);

            $collector->logQuery(new QueryRecord(
                sql: $event->sql,
                rawSql: self::buildRawSql($event->sql, $event->bindings),
                params: $event->bindings,
                line: self::extractCallerLine(),
                startTime: $startTime,
                endTime: $endTime,
            ));
        });
    }

    /**
     * Record a failed query from a QueryException.
     *
     * Called from DebugMiddleware when a QueryException is caught, since
     * Laravel does not fire QueryExecuted for failed queries.
     */
    public function collectFailedQuery(QueryException $exception): void
    {
        $collector = ($this->collectorFactory)();

        $sql = $exception->getSql();
        $bindings = $exception->getBindings();
        $endTime = microtime(true);

        $id = uniqid('failed-', true);
        $collector->collectQueryStart(
            $id,
            $sql,
            self::buildRawSql($sql, $bindings),
            $bindings,
            self::extractCallerLine($exception),
        );
        $collector->collectQueryError($id, $exception);
    }

    private static function buildRawSql(string $sql, array $bindings): string
    {
        $rawSql = $sql;
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? (string) $binding : "'" . addslashes((string) $binding) . "'";
            $rawSql = preg_replace('/\?/', $value, $rawSql, 1) ?? $rawSql;
        }
        return $rawSql;
    }

    /**
     * Extract the first application-level caller from backtrace or exception trace.
     */
    private static function extractCallerLine(?\Throwable $exception = null): string
    {
        $trace = $exception !== null ? $exception->getTrace() : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        foreach ($trace as $frame) {
            if (array_key_exists('file', $frame) && !str_contains($frame['file'], 'vendor/')) {
                return $frame['file'] . ':' . ($frame['line'] ?? 0);
            }
        }

        return '';
    }
}
