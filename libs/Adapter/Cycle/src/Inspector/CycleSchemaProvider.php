<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Cycle\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseProviderInterface;

class CycleSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private DatabaseProviderInterface $databaseProvider,
    ) {}

    public function getTables(): array
    {
        $database = $this->databaseProvider->database();
        $tableSchemas = $database->getTables();

        $tables = [];
        foreach ($tableSchemas as $schema) {
            $records = $database->select()->from($schema->getName())->count();
            $tables[] = [
                'table' => $schema->getName(),
                'primaryKeys' => $schema->getPrimaryKeys(),
                'columns' => $this->serializeCycleColumnsSchemas($schema->getColumns()),
                'records' => $records,
            ];
        }

        return $tables;
    }

    public function getTable(string $tableName, int $limit = SchemaProviderInterface::DEFAULT_LIMIT, int $offset = 0): array
    {
        $database = $this->databaseProvider->database();
        $schema = $database->table($tableName);

        $totalCount = $database->select()->from($tableName)->count();
        $records = $database->select()->from($tableName)->limit($limit)->offset($offset)->fetchAll();

        return [
            'table' => $schema->getName(),
            'primaryKeys' => $schema->getPrimaryKeys(),
            'columns' => $this->serializeCycleColumnsSchemas($schema->getColumns()),
            'records' => $records,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
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

    /**
     * @param ColumnInterface[] $columns
     */
    private function serializeCycleColumnsSchemas(array $columns): array
    {
        $result = [];
        foreach ($columns as $columnSchema) {
            $result[] = [
                'name' => $columnSchema->getName(),
                'size' => $columnSchema->getSize(),
                'type' => $columnSchema->getInternalType(),
                'dbType' => $columnSchema->getType(),
                'defaultValue' => $columnSchema->getDefaultValue(),
                'comment' => null,
                'allowNull' => $columnSchema->isNullable(),
            ];
        }
        return $result;
    }
}
