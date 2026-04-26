<?php

declare(strict_types=1);

namespace App\Application;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;

/**
 * Minimal PDO decorator that times every query and feeds the ADP DatabaseCollector.
 *
 * Wraps the three statement-producing entry points (`query`, `exec`, `prepare`).
 * For prepared statements we delegate to {@see TracingPdoStatement} so the timing
 * spans `PDOStatement::execute()` — the moment the engine actually runs the SQL.
 */
final class TracingPdo extends \PDO
{
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        private ?DatabaseCollector $collector = null,
    ) {
        parent::__construct($dsn, $username, $password, $options ?? []);
    }

    public function setCollector(?DatabaseCollector $collector): void
    {
        $this->collector = $collector;
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        $start = microtime(true);
        $stmt = $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
        $this->log($query, [], $start, $stmt instanceof \PDOStatement ? $stmt->rowCount() : 0);

        return $stmt;
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $rows = parent::exec($statement);
        $this->log($statement, [], $start, $rows === false ? 0 : $rows);

        return $rows;
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $stmt = parent::prepare($query, $options);
        if ($stmt === false) {
            return false;
        }

        return new TracingPdoStatement($stmt, $query, $this->collector);
    }

    /**
     * @internal — used by {@see TracingPdoStatement} too.
     */
    public function log(string $sql, array $params, float $start, int $rows): void
    {
        if ($this->collector === null) {
            return;
        }

        $this->collector->logQuery(new QueryRecord(
            sql: $sql,
            rawSql: $this->renderRaw($sql, $params),
            params: $params,
            line: '',
            startTime: $start,
            endTime: microtime(true),
            rowsNumber: $rows,
        ));
    }

    private function renderRaw(string $sql, array $params): string
    {
        if ($params === []) {
            return $sql;
        }
        $rendered = $sql;
        foreach ($params as $key => $value) {
            $repl = is_string($value) ? "'" . str_replace("'", "''", $value) . "'" : (string) $value;
            $rendered = is_int($key)
                ? preg_replace('/\?/', $repl, $rendered, 1) ?? $rendered
                : str_replace(':' . ltrim((string) $key, ':'), $repl, $rendered);
        }
        return $rendered;
    }
}
