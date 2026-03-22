<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;

/**
 * No-op schema provider for when no database is configured.
 * Returns empty results instead of causing a 500 error.
 */
final class NullSchemaProvider implements SchemaProviderInterface
{
    public function getTables(): array
    {
        return [];
    }

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        return [
            'name' => $tableName,
            'columns' => [],
            'records' => [],
            'total' => 0,
        ];
    }

    public function explainQuery(string $sql, array $params = [], bool $analyze = false): array
    {
        return [];
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        return [];
    }
}
