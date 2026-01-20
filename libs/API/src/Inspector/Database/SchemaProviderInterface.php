<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api\Inspector\Database;

interface SchemaProviderInterface
{
    public function getTables(): array;

    public function getTable(string $tableName): array;
}
