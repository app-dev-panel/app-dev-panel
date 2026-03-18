<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Database;

class NullSchemaProvider implements SchemaProviderInterface
{
    public function getTables(): array
    {
        return [];
    }

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        return [
            'table' => $tableName,
            'primaryKeys' => [],
            'columns' => [],
            'records' => [],
            'totalCount' => 0,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
