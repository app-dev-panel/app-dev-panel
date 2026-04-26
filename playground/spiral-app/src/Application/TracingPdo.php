<?php

declare(strict_types=1);

namespace App\Application;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;

/**
 * Composition-based PDO wrapper that times every query and feeds the ADP
 * `DatabaseCollector`. Composition (rather than `extends \PDO`) keeps us free
 * of PHP 8.4's strict return-type checks on overridden `prepare()` /
 * `query()` / `exec()` — the inner PDO is held privately, our methods return
 * decorated statements, and consumers type-hint on `TracingPdo` directly.
 */
final class TracingPdo
{
    private readonly \PDO $pdo;

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        private ?DatabaseCollector $collector = null,
    ) {
        $this->pdo = new \PDO($dsn, $username, $password, $options ?? []);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function setCollector(?DatabaseCollector $collector): void
    {
        $this->collector = $collector;
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return $this->pdo->setAttribute($attribute, $value);
    }

    public function exec(string $statement): int|false
    {
        $start = microtime(true);
        $rows = $this->pdo->exec($statement);
        $this->log($statement, [], $start, $rows === false ? 0 : $rows);

        return $rows;
    }

    public function query(string $sql): TracingPdoStatement|false
    {
        $start = microtime(true);
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return false;
        }
        $rows = $stmt->rowCount();
        $this->log($sql, [], $start, $rows);

        return new TracingPdoStatement($stmt, $sql, null);
    }

    public function prepare(string $query, array $options = []): TracingPdoStatement|false
    {
        $stmt = $this->pdo->prepare($query, $options);
        if ($stmt === false) {
            return false;
        }

        return new TracingPdoStatement($stmt, $query, $this->collector);
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * @internal
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
