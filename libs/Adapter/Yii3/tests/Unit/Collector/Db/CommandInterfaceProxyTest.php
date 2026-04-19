<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Db;

use AppDevPanel\Adapter\Yii3\Collector\Db\CommandInterfaceProxy;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Query\DataReaderInterface;

final class CommandInterfaceProxyTest extends TestCase
{
    public function testExecuteCollectsQueryAndDelegates(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('INSERT INTO users (name) VALUES (?)');
        $command->method('getRawSql')->willReturn("INSERT INTO users (name) VALUES ('John')");
        $command->method('getParams')->willReturn([':name' => 'John']);
        $command->expects($this->once())->method('execute')->willReturn(1);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->execute();

        $this->assertSame(1, $result);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $query = reset($collected['queries']);
        $this->assertSame('INSERT INTO users (name) VALUES (?)', $query['sql']);
        $this->assertSame("INSERT INTO users (name) VALUES ('John')", $query['rawSql']);
        $this->assertSame([':name' => 'John'], $query['params']);
        $this->assertSame('success', $query['status']);
        $this->assertSame(1, $query['rowsNumber']);
    }

    public function testExecuteCollectsErrorOnException(): void
    {
        $exception = new RuntimeException('DB error');

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('BAD SQL');
        $command->method('getRawSql')->willReturn('BAD SQL');
        $command->method('getParams')->willReturn([]);
        $command->method('execute')->willThrowException($exception);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        try {
            $proxy->execute();
        } finally {
            $collected = $collector->getCollected();
            $this->assertCount(1, $collected['queries']);
            $query = reset($collected['queries']);
            $this->assertSame('error', $query['status']);
            $this->assertSame($exception, $query['exception']);
        }
    }

    public function testQueryAllCollectsRowCount(): void
    {
        $rows = [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT * FROM users');
        $command->method('getRawSql')->willReturn('SELECT * FROM users');
        $command->method('getParams')->willReturn([]);
        $command->expects($this->once())->method('queryAll')->willReturn($rows);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryAll();

        $this->assertSame($rows, $result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(2, $query['rowsNumber']);
        $this->assertSame('success', $query['status']);
    }

    public function testQueryOneCollectsRowCount(): void
    {
        $row = ['id' => 1, 'name' => 'Alice'];

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT * FROM users LIMIT 1');
        $command->method('getRawSql')->willReturn('SELECT * FROM users LIMIT 1');
        $command->method('getParams')->willReturn([]);
        $command->method('queryOne')->willReturn($row);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryOne();

        $this->assertSame($row, $result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(1, $query['rowsNumber']);
    }

    public function testQueryOneNullCollectsZeroRows(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT * FROM users WHERE id = 999');
        $command->method('getRawSql')->willReturn('SELECT * FROM users WHERE id = 999');
        $command->method('getParams')->willReturn([]);
        $command->method('queryOne')->willReturn(null);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryOne();

        $this->assertNull($result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(0, $query['rowsNumber']);
    }

    public function testQueryColumnCollectsRowCount(): void
    {
        $column = [1, 2, 3];

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT id FROM users');
        $command->method('getRawSql')->willReturn('SELECT id FROM users');
        $command->method('getParams')->willReturn([]);
        $command->method('queryColumn')->willReturn($column);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryColumn();

        $this->assertSame($column, $result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(3, $query['rowsNumber']);
    }

    public function testQueryScalarCollectsRowCount(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT COUNT(*) FROM users');
        $command->method('getRawSql')->willReturn('SELECT COUNT(*) FROM users');
        $command->method('getParams')->willReturn([]);
        $command->method('queryScalar')->willReturn(42);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryScalar();

        $this->assertSame(42, $result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(1, $query['rowsNumber']);
    }

    public function testQueryScalarNullCollectsZeroRows(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT name FROM users WHERE id = 999');
        $command->method('getRawSql')->willReturn('SELECT name FROM users WHERE id = 999');
        $command->method('getParams')->willReturn([]);
        $command->method('queryScalar')->willReturn(null);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->queryScalar();

        $this->assertNull($result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(0, $query['rowsNumber']);
    }

    public function testQueryCollectsDataReaderRowCount(): void
    {
        $dataReader = $this->createMock(DataReaderInterface::class);
        $dataReader->method('count')->willReturn(5);

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT * FROM users');
        $command->method('getRawSql')->willReturn('SELECT * FROM users');
        $command->method('getParams')->willReturn([]);
        $command->method('query')->willReturn($dataReader);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $result = $proxy->query();

        $this->assertSame($dataReader, $result);

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertSame(5, $query['rowsNumber']);
    }

    public function testSchemaMethodsReturnNewProxyInstance(): void
    {
        $newCommand = $this->createMock(CommandInterface::class);

        $command = $this->createMock(CommandInterface::class);
        $command->method('createTable')->willReturn($newCommand);
        $command->method('dropTable')->willReturn($newCommand);
        $command->method('addColumn')->willReturn($newCommand);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new CommandInterfaceProxy($command, $collector);

        $result = $proxy->createTable('test', ['id' => 'int']);
        $this->assertInstanceOf(CommandInterfaceProxy::class, $result);
        $this->assertNotSame($proxy, $result);

        $result = $proxy->dropTable('test');
        $this->assertInstanceOf(CommandInterfaceProxy::class, $result);

        $result = $proxy->addColumn('test', 'name', 'string');
        $this->assertInstanceOf(CommandInterfaceProxy::class, $result);
    }

    public function testGetSqlDelegatesToDecorated(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT 1');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new CommandInterfaceProxy($command, $collector);
        $this->assertSame('SELECT 1', $proxy->getSql());
    }

    public function testGetRawSqlDelegatesToDecorated(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getRawSql')->willReturn('SELECT 1');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new CommandInterfaceProxy($command, $collector);
        $this->assertSame('SELECT 1', $proxy->getRawSql());
    }

    public function testGetParamsDelegatesToDecorated(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getParams')->willReturn([':id' => 1]);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new CommandInterfaceProxy($command, $collector);
        $this->assertSame([':id' => 1], $proxy->getParams());
    }

    public function testShowDatabasesDelegatesToDecorated(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('showDatabases')->willReturn(['db1', 'db2']);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new CommandInterfaceProxy($command, $collector);
        $this->assertSame(['db1', 'db2'], $proxy->showDatabases());
    }

    public function testQueryAllErrorCollectsException(): void
    {
        $exception = new RuntimeException('Query failed');

        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT *');
        $command->method('getRawSql')->willReturn('SELECT *');
        $command->method('getParams')->willReturn([]);
        $command->method('queryAll')->willThrowException($exception);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);

        $this->expectException(RuntimeException::class);

        try {
            $proxy->queryAll();
        } finally {
            $collected = $collector->getCollected();
            $query = reset($collected['queries']);
            $this->assertSame('error', $query['status']);
        }
    }

    public function testCollectsCallStackLine(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $command->method('getSql')->willReturn('SELECT 1');
        $command->method('getRawSql')->willReturn('SELECT 1');
        $command->method('getParams')->willReturn([]);
        $command->method('execute')->willReturn(0);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new CommandInterfaceProxy($command, $collector);
        $expectedLine = __LINE__ + 1;
        $proxy->execute();

        $collected = $collector->getCollected();
        $query = reset($collected['queries']);
        $this->assertStringContainsString(__FILE__, $query['line']);
        $this->assertStringContainsString((string) $expectedLine, $query['line']);
    }
}
