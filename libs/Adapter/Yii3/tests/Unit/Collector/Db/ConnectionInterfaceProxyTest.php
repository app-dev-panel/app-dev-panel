<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Db;

use AppDevPanel\Adapter\Yii3\Collector\Db\CommandInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Db\ConnectionInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Db\TransactionInterfaceDecorator;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Closure;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class ConnectionInterfaceProxyTest extends TestCase
{
    public function testCreateCommandReturnsCommandProxy(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('createCommand')->with('SELECT 1', [])->willReturn($command);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $result = $proxy->createCommand('SELECT 1', []);

        $this->assertInstanceOf(CommandInterfaceProxy::class, $result);
    }

    public function testCreateTransactionReturnsTransactionDecorator(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('createTransaction')->willReturn($transaction);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $result = $proxy->createTransaction();

        $this->assertInstanceOf(TransactionInterfaceDecorator::class, $result);
    }

    public function testBeginTransactionCollectsAndReturnsDecorator(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('beginTransaction')->with('SERIALIZABLE')->willReturn($transaction);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $result = $proxy->beginTransaction('SERIALIZABLE');

        $this->assertInstanceOf(TransactionInterfaceDecorator::class, $result);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['transactions']);
        $tx = reset($collected['transactions']);
        $this->assertSame('SERIALIZABLE', $tx['level']);
        $this->assertSame('start', $tx['status']);
    }

    public function testGetTransactionReturnsDecoratorWhenNotNull(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTransaction')->willReturn($transaction);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $result = $proxy->getTransaction();

        $this->assertInstanceOf(TransactionInterfaceDecorator::class, $result);
    }

    public function testGetTransactionReturnsNullWhenNoTransaction(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTransaction')->willReturn(null);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertNull($proxy->getTransaction());
    }

    public function testTransactionCollectsStartAndDelegates(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection
            ->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(fn(Closure $closure) => $closure($connection));

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $callbackExecuted = false;
        $proxy->transaction(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        }, 'READ COMMITTED');

        $this->assertTrue($callbackExecuted);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['transactions']);
        $tx = reset($collected['transactions']);
        $this->assertSame('READ COMMITTED', $tx['level']);
    }

    public function testCloseDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('close');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $proxy->close();
    }

    public function testOpenDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('open');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $proxy->open();
    }

    public function testIsActiveDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('isActive')->willReturn(true);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertTrue($proxy->isActive());
    }

    public function testGetDriverNameDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getDriverName')->willReturn('mysql');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame('mysql', $proxy->getDriverName());
    }

    public function testGetServerVersionDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getServerVersion')->willReturn('8.0.32');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame('8.0.32', $proxy->getServerVersion());
    }

    public function testGetTablePrefixDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTablePrefix')->willReturn('app_');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame('app_', $proxy->getTablePrefix());
    }

    public function testGetSchemaDelegatesToDecorated(): void
    {
        $schema = $this->createMock(SchemaInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getSchema')->willReturn($schema);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame($schema, $proxy->getSchema());
    }

    public function testGetQueryBuilderDelegatesToDecorated(): void
    {
        $qb = $this->createMock(QueryBuilderInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getQueryBuilder')->willReturn($qb);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame($qb, $proxy->getQueryBuilder());
    }

    public function testGetQuoterDelegatesToDecorated(): void
    {
        $quoter = $this->createMock(QuoterInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getQuoter')->willReturn($quoter);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame($quoter, $proxy->getQuoter());
    }

    public function testGetLastInsertIDDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getLastInsertID')->willReturn('123');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame('123', $proxy->getLastInsertID());
    }

    public function testGetTableSchemaDelegatesToDecorated(): void
    {
        $tableSchema = $this->createMock(TableSchemaInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTableSchema')->with('users', false)->willReturn($tableSchema);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame($tableSchema, $proxy->getTableSchema('users'));
    }

    public function testIsSavepointEnabledDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('isSavepointEnabled')->willReturn(true);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertTrue($proxy->isSavepointEnabled());
    }

    public function testQuoteValueDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('quoteValue')->with('test')->willReturn("'test'");

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame("'test'", $proxy->quoteValue('test'));
    }

    public function testSetEnableSavepointDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('setEnableSavepoint')->with(true);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $proxy->setEnableSavepoint(true);
    }

    public function testSetTablePrefixDelegatesToDecorated(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('setTablePrefix')->with('app_');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $proxy->setTablePrefix('app_');
    }

    public function testCreateBatchQueryResultDelegatesToDecorated(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $batchResult = $this->createMock(BatchQueryResultInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('createBatchQueryResult')->with($query, false)->willReturn($batchResult);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $proxy = new ConnectionInterfaceProxy($connection, $collector);
        $this->assertSame($batchResult, $proxy->createBatchQueryResult($query));
    }
}
