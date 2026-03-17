<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use yii\db\Connection;

/**
 * Provides database schema inspection via Yii 2's DB layer.
 *
 * Uses Yii 2's Schema class to list tables, columns, and query records.
 * Registered automatically when a yii\db\Connection is available.
 */
final class Yii2DbSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function getTables(): array
    {
        $schema = $this->connection->getSchema();
        $tableNames = $schema->getTableNames();
        $tables = [];

        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            if ($tableSchema === null) {
                continue;
            }

            $primaryKeys = $tableSchema->primaryKey;
            $columns = [];

            foreach ($tableSchema->columns as $column) {
                $columns[] = [
                    'name' => $column->name,
                    'size' => $column->size,
                    'type' => $column->type,
                    'dbType' => $column->dbType,
                    'defaultValue' => $column->defaultValue,
                    'comment' => $column->comment,
                    'allowNull' => $column->allowNull,
                ];
            }

            $recordCount = 0;
            try {
                $recordCount = (int) $this->connection->createCommand(
                    'SELECT COUNT(*) FROM ' . $this->connection->quoteTableName($tableName),
                )->queryScalar();
            } catch (\Throwable) {
                // Ignore count errors
            }

            $tables[] = [
                'table' => $tableName,
                'primaryKeys' => $primaryKeys,
                'columns' => $columns,
                'records' => $recordCount,
            ];
        }

        return $tables;
    }

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        $schema = $this->connection->getSchema();
        $tableSchema = $schema->getTableSchema($tableName);

        if ($tableSchema === null) {
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

        $columns = [];
        foreach ($tableSchema->columns as $column) {
            $columns[] = [
                'name' => $column->name,
                'size' => $column->size,
                'type' => $column->type,
                'dbType' => $column->dbType,
                'defaultValue' => $column->defaultValue,
                'comment' => $column->comment,
                'allowNull' => $column->allowNull,
            ];
        }

        $quotedName = $this->connection->quoteTableName($tableName);
        $totalCount = (int) $this->connection->createCommand("SELECT COUNT(*) FROM {$quotedName}")->queryScalar();

        $records = $this->connection->createCommand("SELECT * FROM {$quotedName} LIMIT :limit OFFSET :offset")
            ->bindValues([':limit' => $limit, ':offset' => $offset])
            ->queryAll();

        return [
            'table' => $tableName,
            'primaryKeys' => $tableSchema->primaryKey,
            'columns' => $columns,
            'records' => $records,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
