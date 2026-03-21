<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Illuminate\Database\Connection;

/**
 * Provides database schema inspection via Laravel's Illuminate\Database\Connection.
 */
final class LaravelSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function getTables(): array
    {
        $tables = [];
        $schema = $this->connection->getSchemaBuilder();
        $tableNames = $schema->getTableListing();

        foreach ($tableNames as $tableName) {
            $columns = $schema->getColumns($tableName);
            $tables[] = [
                'table' => $tableName,
                'primaryKeys' => $this->getPrimaryKeys($tableName),
                'columns' => $this->serializeColumns($columns),
                'records' => (int) $this->connection->table($tableName)->count(),
            ];
        }

        return $tables;
    }

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        $schema = $this->connection->getSchemaBuilder();
        $columns = $schema->getColumns($tableName);
        $totalCount = (int) $this->connection->table($tableName)->count();
        $records = $this->connection
            ->table($tableName)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // Convert objects to arrays
        $records = array_map(fn(mixed $record): array => (array) $record, $records);

        return [
            'table' => $tableName,
            'primaryKeys' => $this->getPrimaryKeys($tableName),
            'columns' => $this->serializeColumns($columns),
            'records' => $records,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function explainQuery(string $sql, array $params = [], bool $analyze = false): array
    {
        $driver = $this->connection->getDriverName();

        if ($driver === 'sqlite') {
            $prefix = 'EXPLAIN QUERY PLAN ';
        } elseif ($analyze) {
            $prefix = 'EXPLAIN ANALYZE ';
        } else {
            $prefix = 'EXPLAIN ';
        }

        $results = $this->connection->select($prefix . $sql, $params);

        return array_map(fn(mixed $row): array => (array) $row, $results);
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        $results = $this->connection->select($sql, $params);

        return array_map(fn(mixed $row): array => (array) $row, $results);
    }

    /**
     * @return list<string>
     */
    private function getPrimaryKeys(string $tableName): array
    {
        try {
            $indexes = $this->connection->getSchemaBuilder()->getIndexes($tableName);
            foreach ($indexes as $index) {
                if ($index['primary'] ?? false) {
                    return $index['columns'] ?? [];
                }
            }
        } catch (\Throwable) {
            // Gracefully handle missing index info
        }

        return [];
    }

    /**
     * @param list<array<string, mixed>> $columns
     * @return list<array{name: string, size: int|null, type: string, dbType: string, defaultValue: mixed, comment: string|null, allowNull: bool}>
     */
    private function serializeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $result[] = [
                'name' => $column['name'],
                'size' => null,
                'type' => $column['type_name'] ?? $column['type'] ?? 'unknown',
                'dbType' => $column['type'] ?? 'unknown',
                'defaultValue' => $column['default'] ?? null,
                'comment' => $column['comment'] ?? null,
                'allowNull' => (bool) ($column['nullable'] ?? true),
            ];
        }
        return $result;
    }
}
