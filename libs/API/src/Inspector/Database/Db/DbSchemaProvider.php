<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Database\Db;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;

class DbSchemaProvider implements SchemaProviderInterface
{
    public function __construct(private ConnectionInterface $db)
    {
    }

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
                'records' => (new Query($this->db))->from($schema->getName())->count(),
            ];
        }
        return $tables;
    }

    public function getTable(string $tableName): array
    {
        /** @var TableSchemaInterface[] $tableSchemas */
        $schema = $this->db->getSchema()->getTableSchema($tableName);
        $records = (new Query($this->db))->from($schema->getName())->all();
        $data = [];

        // TODO: add pagination
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
