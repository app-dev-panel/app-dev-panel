<?php

declare(strict_types=1);

namespace App\Application;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;

/**
 * Decorates a `PDOStatement` so the ADP DatabaseCollector sees the SQL exactly
 * once per `execute()` call (and not per fetch). All other PDOStatement methods
 * are forwarded unchanged.
 *
 * @mixin \PDOStatement
 */
final class TracingPdoStatement
{
    public function __construct(
        private readonly \PDOStatement $inner,
        private readonly string $sql,
        private readonly ?DatabaseCollector $collector = null,
    ) {}

    public function execute(?array $params = null): bool
    {
        $start = microtime(true);
        $result = $this->inner->execute($params);

        if ($this->collector !== null) {
            $this->collector->logQuery(new QueryRecord(
                sql: $this->sql,
                rawSql: $this->renderRaw($params ?? []),
                params: $params ?? [],
                line: '',
                startTime: $start,
                endTime: microtime(true),
                rowsNumber: $this->inner->rowCount(),
            ));
        }

        return $result;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->inner->{$name}(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->inner->{$name};
    }

    public function __set(string $name, mixed $value): void
    {
        $this->inner->{$name} = $value;
    }

    private function renderRaw(array $params): string
    {
        if ($params === []) {
            return $this->sql;
        }
        $rendered = $this->sql;
        foreach ($params as $key => $value) {
            $repl = is_string($value) ? "'" . str_replace("'", "''", $value) . "'" : (string) $value;
            $rendered = is_int($key)
                ? preg_replace('/\?/', $repl, $rendered, 1) ?? $rendered
                : str_replace(':' . ltrim((string) $key, ':'), $repl, $rendered);
        }
        return $rendered;
    }
}
