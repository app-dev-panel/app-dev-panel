<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Inspector;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
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

    public function getTable(string $tableName, int $limit = 1000, int $offset = 0): array
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

    /**
     * @param ColumnSchemaInterface[] $columns
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
                'allowNull' => $columnSchema->isAllowNull(),
            ];
        }
        return $result;
    }
}
