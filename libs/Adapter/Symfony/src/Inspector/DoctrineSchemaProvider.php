<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;

/**
 * Provides database schema inspection via Doctrine DBAL.
 *
 * Uses DBAL's SchemaManager to list tables, columns, and query records.
 * Registered automatically when a Doctrine DBAL Connection is available in the container.
 */
final class DoctrineSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function getTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = [];

        foreach ($schemaManager->listTables() as $table) {
            $tableName = $table->getName();
            $primaryKey = $table->getPrimaryKey();

            $tables[] = [
                'table' => $tableName,
                'primaryKeys' => $primaryKey !== null ? $primaryKey->getColumns() : [],
                'columns' => $this->serializeColumns($table->getColumns()),
                'records' => (int) $this->connection->fetchOne(
                    'SELECT COUNT(*) FROM ' . $this->connection->quoteIdentifier($tableName),
                ),
            ];
        }

        return $tables;
    }

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable($tableName);
        $primaryKey = $table->getPrimaryKey();

        $quotedName = $this->connection->quoteIdentifier($tableName);
        $totalCount = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM {$quotedName}");

        $records = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$quotedName} LIMIT ? OFFSET ?",
            [$limit, $offset],
            ['integer', 'integer'],
        );

        return [
            'table' => $tableName,
            'primaryKeys' => $primaryKey !== null ? $primaryKey->getColumns() : [],
            'columns' => $this->serializeColumns($table->getColumns()),
            'records' => $records,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @param Column[] $columns
     * @return array<int, array{name: string, size: int|null, type: string, dbType: string, defaultValue: mixed, comment: string|null, allowNull: bool}>
     */
    private function serializeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $result[] = [
                'name' => $column->getName(),
                'size' => $column->getLength(),
                'type' => $column->getType()::class,
                'dbType' => $column->getType()->getSQLDeclaration($column->toArray(), $this->connection->getDatabasePlatform()),
                'defaultValue' => $column->getDefault(),
                'comment' => $column->getComment(),
                'allowNull' => !$column->getNotnull(),
            ];
        }
        return $result;
    }
}
