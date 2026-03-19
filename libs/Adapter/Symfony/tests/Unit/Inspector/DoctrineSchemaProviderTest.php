<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\TestCase;

final class DoctrineSchemaProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Connection::class)) {
            $this->markTestSkipped('doctrine/dbal is not installed.');
        }
    }

    public function testImplementsSchemaProviderInterface(): void
    {
        $connection = $this->createMock(Connection::class);
        $provider = new DoctrineSchemaProvider($connection);

        $this->assertInstanceOf(SchemaProviderInterface::class, $provider);
    }

    public function testGetTablesReturnsTableList(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);

        $idColumn = new Column('id', new IntegerType());
        $nameColumn = new Column('name', new StringType());
        $nameColumn->setLength(255);

        $table = new Table(
            'users',
            [$idColumn, $nameColumn],
            [
                new Index('primary', ['id'], false, true),
            ],
        );

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTables')->willReturn([$table]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quoteIdentifier')->willReturn('"users"');
        $connection->method('fetchOne')->willReturn(42);

        $provider = new DoctrineSchemaProvider($connection);
        $tables = $provider->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame('users', $tables[0]['table']);
        $this->assertSame(['id'], $tables[0]['primaryKeys']);
        $this->assertSame(42, $tables[0]['records']);
        $this->assertCount(2, $tables[0]['columns']);
        $this->assertSame('id', $tables[0]['columns'][0]['name']);
        $this->assertSame('name', $tables[0]['columns'][1]['name']);
    }

    public function testGetTableReturnsTableWithRecords(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);

        $idColumn = new Column('id', new IntegerType());
        $nameColumn = new Column('name', new StringType());

        $table = new Table(
            'users',
            [$idColumn, $nameColumn],
            [
                new Index('primary', ['id'], false, true),
            ],
        );

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('introspectTable')->with('users')->willReturn($table);

        $records = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quoteIdentifier')->willReturn('"users"');
        $connection->method('fetchOne')->willReturn(2);
        $connection->method('fetchAllAssociative')->willReturn($records);

        $provider = new DoctrineSchemaProvider($connection);
        $result = $provider->getTable('users', 20, 0);

        $this->assertSame('users', $result['table']);
        $this->assertSame(['id'], $result['primaryKeys']);
        $this->assertCount(2, $result['columns']);
        $this->assertSame($records, $result['records']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testGetTablesReturnsEmptyForNoTables(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTables')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $provider = new DoctrineSchemaProvider($connection);
        $this->assertSame([], $provider->getTables());
    }
}
