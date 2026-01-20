<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Inspector\Database;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\NullCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Yii\Debug\Api\Inspector\Database\Db\DbSchemaProvider;

final class DbSchemaProviderTest extends TestCase
{
    public function testGetTable(): void
    {
        $db = $this->createConnection();

        // generate tables
        $this->generateTables($db);

        $dbSchemaProvider = new DbSchemaProvider($db);
        $table = $dbSchemaProvider->getTable('test3');

        // test table
        $this->assertSame('test3', $table['table']);
        $this->assertSame(['id'], $table['primaryKeys']);
        $this->assertCount(4, $table['columns']);

        // check six record
        $this->assertEquals(6, $table['records'][5]['id']);
        $this->assertSame('test6', $table['records'][5]['product']);
        $this->assertEquals(6.6, $table['records'][5]['price']);
        $this->assertEquals(0, $table['records'][5]['status']);
    }

    public function testGetTables(): void
    {
        $db = $this->createConnection();

        // generate tables
        $this->generateTables($db);

        $dbSchemaProvider = new DbSchemaProvider($db);
        $tables = $dbSchemaProvider->getTables();

        // general tables
        $this->assertCount(3, $tables);

        // test table
        $this->assertSame('test', $tables[0]['table']);
        $this->assertSame(['id'], $tables[0]['primaryKeys']);
        $this->assertCount(2, $tables[0]['columns']);
        $this->assertSame(3, $tables[0]['records']);

        // test2 table
        $this->assertSame('test2', $tables[1]['table']);
        $this->assertSame(['id'], $tables[1]['primaryKeys']);
        $this->assertCount(3, $tables[1]['columns']);
        $this->assertSame(2, $tables[1]['records']);

        // test3 table
        $this->assertSame('test3', $tables[2]['table']);
        $this->assertSame(['id'], $tables[2]['primaryKeys']);
        $this->assertCount(4, $tables[2]['columns']);
        $this->assertSame(10, $tables[2]['records']);

        $db->close();
    }

    private function createConnection(): ConnectionInterface
    {
        return new Connection(new Driver('sqlite::memory:'), new SchemaCache(new NullCache()));
    }

    private function generateTables(ConnectionInterface $db): void
    {
        // create tables
        $db->createCommand()->createTable(
            'test',
            [
                'id' => 'pk',
                'email' => 'string',
            ]
        )->execute();

        $db->createCommand()->createTable(
            'test2',
            [
                'id' => 'pk',
                'name' => 'string',
                'flag' => 'integer',
            ],
        )->execute();

        $db->createCommand()->createTable(
            'test3',
            [
                'id' => 'pk',
                'product' => 'string',
                'price' => 'float',
                'status' => 'integer',
            ],
        )->execute();

        // insert data
        $db->createCommand()->batchInsert(
            'test',
            [
                'id',
                'email',
            ],
            [
                [1, 'test1'],
                [2, 'test2'],
                [3, 'test3'],
            ],
        )->execute();

        $db->createCommand()->batchInsert(
            'test2',
            [
                'id',
                'name',
                'flag',
            ],
            [
                [1, 'test1', 1],
                [2, 'test2', 0],
            ],
        )->execute();

        $db->createCommand()->batchInsert(
            'test3',
            [
                'id',
                'product',
                'price',
                'status',
            ],
            [
                [1, 'test1', 1.1, 1],
                [2, 'test2', 2.2, 0],
                [3, 'test3', 3.3, 1],
                [4, 'test4', 4.4, 0],
                [5, 'test5', 5.5, 1],
                [6, 'test6', 6.6, 0],
                [7, 'test7', 7.7, 1],
                [8, 'test8', 8.8, 0],
                [9, 'test9', 9.9, 1],
                [10, 'test10', 10.10, 0],
            ],
        )->execute();
    }
}
