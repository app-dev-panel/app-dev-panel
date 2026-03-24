<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class LaravelSchemaProviderTest extends TestCase
{
    public function testGetTablesReturnsTableList(): void
    {
        $schemaBuilder = $this->createMock(SchemaBuilder::class);
        $schemaBuilder->method('getTableListing')->willReturn(['users', 'posts']);
        $schemaBuilder
            ->method('getColumns')
            ->willReturnMap([
                [
                    'users',
                    [[
                        'name' => 'id',
                        'type' => 'integer',
                        'type_name' => 'int',
                        'nullable' => false,
                        'default' => null,
                        'comment' => null,
                    ]],
                ],
                [
                    'posts',
                    [[
                        'name' => 'id',
                        'type' => 'integer',
                        'type_name' => 'int',
                        'nullable' => false,
                        'default' => null,
                        'comment' => null,
                    ]],
                ],
            ]);
        $schemaBuilder->method('getIndexes')->willReturn([]);

        $queryBuilder = $this->createMock(Builder::class);
        $queryBuilder->method('count')->willReturn(10);

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchemaBuilder')->willReturn($schemaBuilder);
        $connection->method('table')->willReturn($queryBuilder);

        $provider = new LaravelSchemaProvider($connection);
        $tables = $provider->getTables();

        $this->assertCount(2, $tables);
        $this->assertSame('users', $tables[0]['table']);
        $this->assertSame('posts', $tables[1]['table']);
        $this->assertSame(10, $tables[0]['records']);
    }

    public function testGetTableReturnsDetailedData(): void
    {
        $columns = [
            [
                'name' => 'id',
                'type' => 'integer',
                'type_name' => 'int',
                'nullable' => false,
                'default' => null,
                'comment' => 'Primary key',
            ],
            [
                'name' => 'name',
                'type' => 'varchar(255)',
                'type_name' => 'varchar',
                'nullable' => true,
                'default' => 'John',
                'comment' => null,
            ],
        ];

        $schemaBuilder = $this->createMock(SchemaBuilder::class);
        $schemaBuilder->method('getColumns')->willReturn($columns);
        $schemaBuilder
            ->method('getIndexes')
            ->willReturn([
                ['primary' => true, 'columns' => ['id']],
            ]);

        $records = [(object) ['id' => 1, 'name' => 'Alice'], (object) ['id' => 2, 'name' => 'Bob']];

        $queryBuilder = $this->createMock(Builder::class);
        $queryBuilder->method('count')->willReturn(2);
        $queryBuilder->method('offset')->willReturnSelf();
        $queryBuilder->method('limit')->willReturnSelf();
        $queryBuilder->method('get')->willReturn(new Collection($records));

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchemaBuilder')->willReturn($schemaBuilder);
        $connection->method('table')->willReturn($queryBuilder);

        $provider = new LaravelSchemaProvider($connection);
        $result = $provider->getTable('users', 20, 0);

        $this->assertSame('users', $result['table']);
        $this->assertSame(['id'], $result['primaryKeys']);
        $this->assertCount(2, $result['columns']);
        $this->assertSame('int', $result['columns'][0]['type']);
        $this->assertSame('varchar', $result['columns'][1]['type']);
        $this->assertSame('Primary key', $result['columns'][0]['comment']);
        $this->assertFalse($result['columns'][0]['allowNull']);
        $this->assertTrue($result['columns'][1]['allowNull']);
        $this->assertCount(2, $result['records']);
        $this->assertSame(2, $result['totalCount']);
    }

    public function testExplainQuery(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('select')->willReturn([(object) ['id' => 1, 'select_type' => 'SIMPLE']]);

        $provider = new LaravelSchemaProvider($connection);
        $result = $provider->explainQuery('SELECT * FROM users');

        $this->assertCount(1, $result);
        $this->assertSame('SIMPLE', $result[0]['select_type']);
    }

    public function testExplainQueryWithAnalyze(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriverName')->willReturn('mysql');
        $connection
            ->expects($this->once())
            ->method('select')
            ->with($this->stringStartsWith('EXPLAIN ANALYZE '), [])
            ->willReturn([]);

        $provider = new LaravelSchemaProvider($connection);
        $provider->explainQuery('SELECT 1', [], true);
    }

    public function testExplainQuerySqliteUsesQueryPlan(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriverName')->willReturn('sqlite');
        $connection
            ->expects($this->once())
            ->method('select')
            ->with($this->stringStartsWith('EXPLAIN QUERY PLAN '), [])
            ->willReturn([]);

        $provider = new LaravelSchemaProvider($connection);
        $provider->explainQuery('SELECT 1');
    }

    public function testExecuteQuery(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn([(object) ['count' => 42]]);

        $provider = new LaravelSchemaProvider($connection);
        $result = $provider->executeQuery('SELECT COUNT(*) as count FROM users');

        $this->assertCount(1, $result);
        $this->assertSame(42, $result[0]['count']);
    }

    public function testImplementsSchemaProviderInterface(): void
    {
        $connection = $this->createMock(Connection::class);
        $provider = new LaravelSchemaProvider($connection);

        $this->assertInstanceOf(SchemaProviderInterface::class, $provider);
    }
}
