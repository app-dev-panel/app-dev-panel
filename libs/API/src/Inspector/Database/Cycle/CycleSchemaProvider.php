<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Database\Cycle;

use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseProviderInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;

class CycleSchemaProvider implements SchemaProviderInterface
{
    public function __construct(private DatabaseProviderInterface $databaseProvider)
    {
    }

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

    public function getTable(string $tableName): array
    {
        $database = $this->databaseProvider->database();
        $schema = $database->table($tableName);

        // TODO: add pagination
        $records = $database->select()->from($tableName)->fetchAll();

        return [
            'table' => $schema->getName(),
            'primaryKeys' => $schema->getPrimaryKeys(),
            'columns' => $this->serializeCycleColumnsSchemas($schema->getColumns()),
            'records' => $records,
        ];
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
                'comment' => null, // unsupported for now
                'allowNull' => $columnSchema->isNullable(),
            ];
        }
        return $result;
    }
}
