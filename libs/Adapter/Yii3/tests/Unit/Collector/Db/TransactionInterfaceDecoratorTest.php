<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Db;

use AppDevPanel\Adapter\Yii3\Collector\Db\TransactionInterfaceDecorator;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Transaction\TransactionInterface;

final class TransactionInterfaceDecoratorTest extends TestCase
{
    public function testBeginCollectsTransactionStart(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('begin')->with('SERIALIZABLE');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->begin('SERIALIZABLE');

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['transactions']);
        $tx = reset($collected['transactions']);
        $this->assertSame('SERIALIZABLE', $tx['level']);
        $this->assertSame('start', $tx['status']);
    }

    public function testCommitDelegatesToDecoratedAndCollects(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('commit');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        // Start a transaction first so commit has something to update
        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->begin();

        $decorator->commit();

        $collected = $collector->getCollected();
        $tx = reset($collected['transactions']);
        $this->assertSame('commit', $tx['status']);
    }

    public function testRollBackDelegatesToDecoratedAndCollects(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('rollBack');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->begin();

        $decorator->rollBack();

        $collected = $collector->getCollected();
        $tx = reset($collected['transactions']);
        $this->assertSame('rollback', $tx['status']);
    }

    public function testGetLevelDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->method('getLevel')->willReturn(2);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $this->assertSame(2, $decorator->getLevel());
    }

    public function testIsActiveDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->method('isActive')->willReturn(true);

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $this->assertTrue($decorator->isActive());
    }

    public function testSetIsolationLevelDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('setIsolationLevel')->with('READ COMMITTED');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->setIsolationLevel('READ COMMITTED');
    }

    public function testCreateSavepointDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('createSavepoint')->with('sp1');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->createSavepoint('sp1');
    }

    public function testRollBackSavepointDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('rollBackSavepoint')->with('sp1');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->rollBackSavepoint('sp1');
    }

    public function testReleaseSavepointDelegatesToDecorated(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('releaseSavepoint')->with('sp1');

        $timeline = new TimelineCollector();
        $collector = new DatabaseCollector($timeline);

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->releaseSavepoint('sp1');
    }

    public function testCommitCollectsCallStackLine(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->begin();
        $decorator->commit();

        $collected = $collector->getCollected();
        $tx = reset($collected['transactions']);
        // The commit action should have a line reference
        $commitAction = end($tx['actions']);
        $this->assertSame('transaction.commit', $commitAction['action']);
        $this->assertStringContainsString(__FILE__, $commitAction['line']);
    }

    public function testRollBackCollectsCallStackLine(): void
    {
        $transaction = $this->createMock(TransactionInterface::class);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new DatabaseCollector($timeline);
        $collector->startup();

        $decorator = new TransactionInterfaceDecorator($transaction, $collector);
        $decorator->begin();
        $decorator->rollBack();

        $collected = $collector->getCollected();
        $tx = reset($collected['transactions']);
        $rollbackAction = end($tx['actions']);
        $this->assertSame('transaction.rollback', $rollbackAction['action']);
        $this->assertStringContainsString(__FILE__, $rollbackAction['line']);
    }
}
