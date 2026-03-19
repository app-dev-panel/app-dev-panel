<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2DbSchemaProvider;
use PHPUnit\Framework\TestCase;
use yii\db\ColumnSchema;
use yii\db\Command;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;

final class Yii2DbSchemaProviderTest extends TestCase
{
    public function testGetTablesReturnsTableList(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->name = 'id';
        $columnSchema->type = 'integer';
        $columnSchema->dbType = 'int(11)';
        $columnSchema->size = 11;
        $columnSchema->allowNull = false;
        $columnSchema->defaultValue = null;
        $columnSchema->comment = 'Primary key';

        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->primaryKey = ['id'];
        $tableSchema->columns = ['id' => $columnSchema];

        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['users']);
        $schema->method('getTableSchema')->with('users')->willReturn($tableSchema);

        $countCommand = $this->createMock(Command::class);
        $countCommand->method('queryScalar')->willReturn('5');

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchema')->willReturn($schema);
        $connection->method('quoteTableName')->willReturn('`users`');
        $connection->method('createCommand')->willReturn($countCommand);

        $provider = new Yii2DbSchemaProvider($connection);
        $tables = $provider->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame('users', $tables[0]['table']);
        $this->assertSame(['id'], $tables[0]['primaryKeys']);
        $this->assertSame(5, $tables[0]['records']);

        $columns = $tables[0]['columns'];
        $this->assertCount(1, $columns);
        $this->assertSame('id', $columns[0]['name']);
        $this->assertSame('integer', $columns[0]['type']);
        $this->assertSame('int(11)', $columns[0]['dbType']);
        $this->assertFalse($columns[0]['allowNull']);
    }

    public function testGetTableReturnsRecords(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->name = 'name';
        $columnSchema->type = 'string';
        $columnSchema->dbType = 'varchar(255)';
        $columnSchema->size = 255;
        $columnSchema->allowNull = true;
        $columnSchema->defaultValue = null;
        $columnSchema->comment = '';

        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->primaryKey = ['id'];
        $tableSchema->columns = ['name' => $columnSchema];

        $schema = $this->createMock(Schema::class);
        $schema->method('getTableSchema')->with('users')->willReturn($tableSchema);

        $countCommand = $this->createMock(Command::class);
        $countCommand->method('queryScalar')->willReturn('3');

        $selectCommand = $this->createMock(Command::class);
        $selectCommand->method('bindValues')->willReturnSelf();
        $selectCommand
            ->method('queryAll')
            ->willReturn([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
                ['id' => 3, 'name' => 'Charlie'],
            ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchema')->willReturn($schema);
        $connection->method('quoteTableName')->willReturn('`users`');
        $connection->method('createCommand')->willReturnCallback(static fn(string $sql) => str_contains($sql, 'COUNT')
            ? $countCommand
            : $selectCommand);

        $provider = new Yii2DbSchemaProvider($connection);
        $result = $provider->getTable('users', 10, 0);

        $this->assertSame('users', $result['table']);
        $this->assertSame(['id'], $result['primaryKeys']);
        $this->assertSame(3, $result['totalCount']);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(0, $result['offset']);
        $this->assertCount(3, $result['records']);
        $this->assertSame('Alice', $result['records'][0]['name']);
    }

    public function testGetTableWithNonExistentTable(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableSchema')->with('nonexistent')->willReturn(null);

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchema')->willReturn($schema);

        $provider = new Yii2DbSchemaProvider($connection);
        $result = $provider->getTable('nonexistent');

        $this->assertSame('nonexistent', $result['table']);
        $this->assertSame([], $result['primaryKeys']);
        $this->assertSame([], $result['columns']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['totalCount']);
    }

    public function testGetTablesHandlesCountError(): void
    {
        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->primaryKey = [];
        $tableSchema->columns = [];

        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['broken_table']);
        $schema->method('getTableSchema')->willReturn($tableSchema);

        $countCommand = $this->createMock(Command::class);
        $countCommand->method('queryScalar')->willThrowException(new \RuntimeException('DB error'));

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchema')->willReturn($schema);
        $connection->method('quoteTableName')->willReturn('`broken_table`');
        $connection->method('createCommand')->willReturn($countCommand);

        $provider = new Yii2DbSchemaProvider($connection);
        $tables = $provider->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame(0, $tables[0]['records']);
    }
}
