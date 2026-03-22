<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Inspector;

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
                'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
                'records' => new Query($this->db)
                    ->from($schema->getName())
                    ->count(),
            ];
        }
        return $tables;
    }

    public function getTable(string $tableName, int $limit = SchemaProviderInterface::DEFAULT_LIMIT, int $offset = 0): array
    {
        /** @var TableSchemaInterface[] $tableSchemas */
        $schema = $this->db->getSchema()->getTableSchema($tableName);
        $totalCount = new Query($this->db)
            ->from($schema->getName())
            ->count();
        $records = new Query($this->db)
            ->from($schema->getName())
            ->limit($limit)
            ->offset($offset)
            ->all();
        $data = [];

        foreach ($records as $r => $record) {
            foreach ($record as $n => $attribute) {
                $data[$r][$n] = $attribute;
            }
        }

        return [
            'table' => $schema->getName(),
            'primaryKeys' => $schema->getPrimaryKey(),
            'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
            'records' => $data,
            'totalCount' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function explainQuery(string $sql, array $params = [], bool $analyze = false): array
    {
        $prefix = $this->isSqlite() ? 'EXPLAIN QUERY PLAN ' : 'EXPLAIN ';
        if ($analyze && !$this->isSqlite()) {
            $prefix = 'EXPLAIN ANALYZE ';
        }

        $command = $this->db->createCommand($prefix . $sql);
        if ($params !== []) {
            // PDO positional params are 1-based, but JSON arrays are 0-based
            if (array_is_list($params)) {
                $reindexed = [];
                foreach ($params as $i => $value) {
                    $reindexed[$i + 1] = $value;
                }
                $command->bindValues($reindexed);
            } else {
                $command->bindValues($params);
            }
        }

        return $command->queryAll();
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        $command = $this->db->createCommand($sql);
        if ($params !== []) {
            if (array_is_list($params)) {
                $reindexed = [];
                foreach ($params as $i => $value) {
                    $reindexed[$i + 1] = $value;
                }
                $command->bindValues($reindexed);
            } else {
                $command->bindValues($params);
            }
        }

        return $command->queryAll();
    }

    private function isSqlite(): bool
    {
        return $this->db->getDriverName() === 'sqlite';
    }

    /**
     * @param ColumnInterface[] $columns
     */
    private function serializeARColumnsSchemas(array $columns): array
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
                'allowNull' => !($columnSchema->isNotNull() ?? false),
            ];
        }
        return $result;
    }
}
