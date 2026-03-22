<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Listens for Illuminate\Database\Events\QueryExecuted and feeds the DatabaseCollector.
 *
 * Laravel fires QueryExecuted for every database query when event listeners are registered.
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

            $bindings = $event->bindings;
            $sql = $event->sql;
            $rawSql = $sql;

            // Build raw SQL with bindings substituted
            foreach ($bindings as $binding) {
                $value = is_numeric($binding) ? (string) $binding : "'" . addslashes((string) $binding) . "'";
                $rawSql = preg_replace('/\?/', $value, $rawSql, 1) ?? $rawSql;
            }

            $endTime = microtime(true);
            $startTime = $endTime - ($event->time / 1000);

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $line = '';
            foreach ($trace as $frame) {
                if (!(array_key_exists('file', $frame) && !str_contains($frame['file'], 'vendor/'))) {
                    continue;
                }

                $line = $frame['file'] . ':' . ($frame['line'] ?? 0);
                break;
            }

            $collector->logQuery(
                sql: $sql,
                rawSql: $rawSql,
                params: $bindings,
                line: $line,
                startTime: $startTime,
                endTime: $endTime,
                rowsNumber: 0,
            );
        });
    }
}
