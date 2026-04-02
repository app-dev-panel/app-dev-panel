<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

class DbSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function getTables(): array
    {
        $quoter = $this->db->getQuoter();
        /** @var TableSchemaInterface[] $tableSchemas */
        $tableSchemas = $this->db->getSchema()->getTableSchemas();
        $tables = [];

        foreach ($tableSchemas as $schema) {
            $tables[] = [
                'table' => $quoter->unquoteSimpleTableName($schema->getName()),
                'primaryKeys' => $schema->getPrimaryKey(),
                'columns' => $this->serializeColumns($schema->getColumns()),
                'records' => $this->tableQuery($schema)->count(),
            ];
        }
        return $tables;
    }

    public function getTable(
        string $tableName,
        int $limit = SchemaProviderInterface::DEFAULT_LIMIT,
        int $offset = 0,
    ): array {
        $schema = $this->db->getSchema()->getTableSchema($tableName);

        return [
            'table' => $schema->getName(),
            'primaryKeys' => $schema->getPrimaryKey(),
            'columns' => $this->serializeColumns($schema->getColumns()),
            'records' => $this
                ->tableQuery($schema)
                ->limit($limit)
                ->offset($offset)
                ->all(),
            'totalCount' => $this->tableQuery($schema)->count(),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function explainQuery(string $sql, array $params = [], bool $analyze = false): array
    {
        $prefix = $this->buildExplainPrefix($analyze);

        return $this->executeWithParams($prefix . $sql, $params);
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        return $this->executeWithParams($sql, $params);
    }

    private function tableQuery(TableSchemaInterface $schema): Query
    {
        return new Query($this->db)->from($schema->getName());
    }

    private function buildExplainPrefix(bool $analyze): string
    {
        if ($this->db->getDriverName() === 'sqlite') {
            return 'EXPLAIN QUERY PLAN ';
        }

        return $analyze ? 'EXPLAIN ANALYZE ' : 'EXPLAIN ';
    }

    private function executeWithParams(string $sql, array $params): array
    {
        $command = $this->db->createCommand($sql);
        if ($params !== []) {
            // PDO positional params are 1-based, but JSON arrays are 0-based
            $command->bindValues(array_is_list($params) ? array_combine(range(1, count($params)), $params) : $params);
        }

        return $command->queryAll();
    }

    /**
     * @param ColumnInterface[] $columns
     */
    private function serializeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $columnSchema) {
            $result[] = [
                'name' => $columnSchema->getName(),
                'size' => $columnSchema->getSize(),
                'type' => $columnSchema->getType(),
                'dbType' => $columnSchema->getDbType(),
                'defaultValue' => $columnSchema->getDefaultValue(),
                'comment' => $columnSchema->getComment(),
                'allowNull' => $columnSchema->isAllowNull(),
            ];
        }
        return $result;
    }
}
