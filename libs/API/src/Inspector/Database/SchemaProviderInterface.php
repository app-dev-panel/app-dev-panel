<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Database;

interface SchemaProviderInterface
{
    public function getTables(): array;

    public function getTable(string $tableName, int $limit = 50, int $offset = 0): array;

    public function explainQuery(string $sql, array $params = [], bool $analyze = false): array;

    public function executeQuery(string $sql, array $params = []): array;
}
