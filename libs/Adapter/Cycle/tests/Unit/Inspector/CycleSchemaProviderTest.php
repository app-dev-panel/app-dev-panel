<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Cycle\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\TableInterface;
use PHPUnit\Framework\TestCase;

final class CycleSchemaProviderTest extends TestCase
{
    public function testImplementsSchemaProviderInterface(): void
    {
        $databaseProvider = $this->createMock(DatabaseProviderInterface::class);
        $provider = new CycleSchemaProvider($databaseProvider);

        $this->assertInstanceOf(SchemaProviderInterface::class, $provider);
    }

    public function testGetTablesReturnsTableList(): void
    {
        $column = $this->createColumn('id', 11, 'int', 'integer', null, false);

        $table = $this->createMock(TableInterface::class);
        $table->method('getName')->willReturn('users');
        $table->method('getPrimaryKeys')->willReturn(['id']);
        $table->method('getColumns')->willReturn([$column]);

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturn(42);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getTables')->willReturn([$table]);
        $database->method('select')->willReturn($countQuery);

        $provider = $this->createProvider($database);
        $tables = $provider->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame('users', $tables[0]['table']);
        $this->assertSame(['id'], $tables[0]['primaryKeys']);
        $this->assertSame(42, $tables[0]['records']);
        $this->assertCount(1, $tables[0]['columns']);
        $this->assertSame('id', $tables[0]['columns'][0]['name']);
        $this->assertSame(11, $tables[0]['columns'][0]['size']);
        $this->assertSame('int', $tables[0]['columns'][0]['type']);
        $this->assertSame('integer', $tables[0]['columns'][0]['dbType']);
        $this->assertNull($tables[0]['columns'][0]['defaultValue']);
        $this->assertNull($tables[0]['columns'][0]['comment']);
        $this->assertFalse($tables[0]['columns'][0]['allowNull']);
    }

    public function testGetTablesWithMultipleTables(): void
    {
        $idColumn = $this->createColumn('id', 11, 'int', 'integer', null, false);
        $nameColumn = $this->createColumn('name', 255, 'string', 'varchar(255)', '', true);

        $usersTable = $this->createMock(TableInterface::class);
        $usersTable->method('getName')->willReturn('users');
        $usersTable->method('getPrimaryKeys')->willReturn(['id']);
        $usersTable->method('getColumns')->willReturn([$idColumn, $nameColumn]);

        $postsTable = $this->createMock(TableInterface::class);
        $postsTable->method('getName')->willReturn('posts');
        $postsTable->method('getPrimaryKeys')->willReturn(['id']);
        $postsTable->method('getColumns')->willReturn([$idColumn]);

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturnOnConsecutiveCalls(10, 5);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getTables')->willReturn([$usersTable, $postsTable]);
        $database->method('select')->willReturn($countQuery);

        $provider = $this->createProvider($database);
        $tables = $provider->getTables();

        $this->assertCount(2, $tables);
        $this->assertSame('users', $tables[0]['table']);
        $this->assertSame(10, $tables[0]['records']);
        $this->assertCount(2, $tables[0]['columns']);
        $this->assertSame('posts', $tables[1]['table']);
        $this->assertSame(5, $tables[1]['records']);
        $this->assertCount(1, $tables[1]['columns']);
    }

    public function testGetTablesReturnsEmptyForNoTables(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getTables')->willReturn([]);

        $provider = $this->createProvider($database);
        $this->assertSame([], $provider->getTables());
    }

    public function testGetTableReturnsTableWithRecords(): void
    {
        $idColumn = $this->createColumn('id', 11, 'int', 'integer', null, false);
        $nameColumn = $this->createColumn('name', 255, 'string', 'varchar(255)', '', true);

        $table = $this->createMock(TableInterface::class);
        $table->method('getName')->willReturn('users');
        $table->method('getPrimaryKeys')->willReturn(['id']);
        $table->method('getColumns')->willReturn([$idColumn, $nameColumn]);

        $records = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturn(2);

        $recordsQuery = $this->createMock(SelectQuery::class);
        $recordsQuery->method('from')->willReturn($recordsQuery);
        $recordsQuery->method('limit')->willReturn($recordsQuery);
        $recordsQuery->method('offset')->willReturn($recordsQuery);
        $recordsQuery->method('fetchAll')->willReturn($records);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('table')->with('users')->willReturn($table);
        $database->method('select')->willReturnOnConsecutiveCalls($countQuery, $recordsQuery);

        $provider = $this->createProvider($database);
        $result = $provider->getTable('users', 20, 0);

        $this->assertSame('users', $result['table']);
        $this->assertSame(['id'], $result['primaryKeys']);
        $this->assertCount(2, $result['columns']);
        $this->assertSame('id', $result['columns'][0]['name']);
        $this->assertSame('name', $result['columns'][1]['name']);
        $this->assertSame($records, $result['records']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testGetTableWithPagination(): void
    {
        $column = $this->createColumn('id', 11, 'int', 'integer', null, false);

        $table = $this->createMock(TableInterface::class);
        $table->method('getName')->willReturn('items');
        $table->method('getPrimaryKeys')->willReturn(['id']);
        $table->method('getColumns')->willReturn([$column]);

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturn(100);

        $recordsQuery = $this->createMock(SelectQuery::class);
        $recordsQuery->method('from')->willReturn($recordsQuery);
        $recordsQuery->method('limit')->with(10)->willReturn($recordsQuery);
        $recordsQuery->method('offset')->with(20)->willReturn($recordsQuery);
        $recordsQuery->method('fetchAll')->willReturn([['id' => 21], ['id' => 22]]);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('table')->with('items')->willReturn($table);
        $database->method('select')->willReturnOnConsecutiveCalls($countQuery, $recordsQuery);

        $provider = $this->createProvider($database);
        $result = $provider->getTable('items', 10, 20);

        $this->assertSame(100, $result['totalCount']);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(20, $result['offset']);
        $this->assertCount(2, $result['records']);
    }

    public function testGetTableUsesDefaultLimit(): void
    {
        $table = $this->createMock(TableInterface::class);
        $table->method('getName')->willReturn('users');
        $table->method('getPrimaryKeys')->willReturn([]);
        $table->method('getColumns')->willReturn([]);

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturn(0);

        $recordsQuery = $this->createMock(SelectQuery::class);
        $recordsQuery->method('from')->willReturn($recordsQuery);
        $recordsQuery->method('limit')->with(SchemaProviderInterface::DEFAULT_LIMIT)->willReturn($recordsQuery);
        $recordsQuery->method('offset')->with(0)->willReturn($recordsQuery);
        $recordsQuery->method('fetchAll')->willReturn([]);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('table')->with('users')->willReturn($table);
        $database->method('select')->willReturnOnConsecutiveCalls($countQuery, $recordsQuery);

        $provider = $this->createProvider($database);
        $result = $provider->getTable('users');

        $this->assertSame(SchemaProviderInterface::DEFAULT_LIMIT, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testExplainQueryReturnsEmptyArray(): void
    {
        $provider = $this->createProvider($this->createMock(DatabaseInterface::class));

        $this->assertSame([], $provider->explainQuery('SELECT 1'));
        $this->assertSame([], $provider->explainQuery('SELECT * FROM users', ['id' => 1], true));
    }

    public function testExecuteQueryReturnsEmptyArray(): void
    {
        $provider = $this->createProvider($this->createMock(DatabaseInterface::class));

        $this->assertSame([], $provider->executeQuery('SELECT 1'));
        $this->assertSame([], $provider->executeQuery('INSERT INTO users VALUES (?)', [1]));
    }

    public function testColumnSerializationWithDefaultValue(): void
    {
        $column = $this->createColumn('status', 50, 'string', 'varchar(50)', 'active', true);

        $table = $this->createMock(TableInterface::class);
        $table->method('getName')->willReturn('orders');
        $table->method('getPrimaryKeys')->willReturn([]);
        $table->method('getColumns')->willReturn([$column]);

        $countQuery = $this->createMock(SelectQuery::class);
        $countQuery->method('from')->willReturn($countQuery);
        $countQuery->method('count')->willReturn(0);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getTables')->willReturn([$table]);
        $database->method('select')->willReturn($countQuery);

        $provider = $this->createProvider($database);
        $tables = $provider->getTables();

        $this->assertSame('active', $tables[0]['columns'][0]['defaultValue']);
        $this->assertTrue($tables[0]['columns'][0]['allowNull']);
        $this->assertSame('varchar(50)', $tables[0]['columns'][0]['dbType']);
    }

    private function createProvider(DatabaseInterface $database): CycleSchemaProvider
    {
        $databaseProvider = $this->createMock(DatabaseProviderInterface::class);
        $databaseProvider->method('database')->willReturn($database);

        return new CycleSchemaProvider($databaseProvider);
    }

    private function createColumn(
        string $name,
        int $size,
        string $internalType,
        string $type,
        mixed $defaultValue,
        bool $nullable,
    ): ColumnInterface {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn($name);
        $column->method('getSize')->willReturn($size);
        $column->method('getInternalType')->willReturn($internalType);
        $column->method('getType')->willReturn($type);
        $column->method('getDefaultValue')->willReturn($defaultValue);
        $column->method('isNullable')->willReturn($nullable);

        return $column;
    }
}
