<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Queue;

use AppDevPanel\Adapter\Yii3\Collector\Queue\QueueDecorator;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class QueueDecoratorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Queue\QueueInterface::class, true)) {
            $this->markTestSkipped('yiisoft/queue is not installed.');
        }
    }

    public function testStatusDelegatesToDecoratedAndCollects(): void
    {
        $status = \Yiisoft\Queue\JobStatus::waiting();

        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->expects($this->once())->method('status')->with('job-123')->willReturn($status);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $decorator = new QueueDecorator($queue, $collector);
        $result = $decorator->status('job-123');

        $this->assertSame($status, $result);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['statuses']);
        $this->assertSame('job-123', $collected['statuses'][0]['id']);
        $this->assertSame($status->key(), $collected['statuses'][0]['status']);
    }

    public function testPushDelegatesToDecoratedAndCollects(): void
    {
        $message = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);
        $pushedMessage = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);

        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->expects($this->once())->method('push')->with($message)->willReturn($pushedMessage);
        $queue->method('getName')->willReturn('default');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $decorator = new QueueDecorator($queue, $collector);
        $result = $decorator->push($message);

        $this->assertSame($pushedMessage, $result);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['pushes']['default']);
        $this->assertSame($pushedMessage, $collected['pushes']['default'][0]['message']);
        $this->assertSame([], $collected['pushes']['default'][0]['middlewares']);
    }

    public function testRunDelegatesToDecorated(): void
    {
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->expects($this->once())->method('run')->with(5)->willReturn(3);

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $decorator = new QueueDecorator($queue, $collector);
        $this->assertSame(3, $decorator->run(5));
    }

    public function testListenDelegatesToDecorated(): void
    {
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->expects($this->once())->method('listen');

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $decorator = new QueueDecorator($queue, $collector);
        $decorator->listen();
    }

    public function testGetNameDelegatesToDecorated(): void
    {
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->method('getName')->willReturn('emails');

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $decorator = new QueueDecorator($queue, $collector);
        $this->assertSame('emails', $decorator->getName());
    }

    public function testWithAdapterReturnsNewDecoratorInstance(): void
    {
        $adapter = $this->createMock(\Yiisoft\Queue\Adapter\AdapterInterface::class);
        $newQueue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);

        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->expects($this->once())->method('withAdapter')->with($adapter, 'new-queue')->willReturn($newQueue);

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $decorator = new QueueDecorator($queue, $collector);
        $newDecorator = $decorator->withAdapter($adapter, 'new-queue');

        $this->assertInstanceOf(QueueDecorator::class, $newDecorator);
        $this->assertNotSame($decorator, $newDecorator);
    }

    public function testPushUpdatesSummary(): void
    {
        $message = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);

        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->method('push')->willReturn($message);
        $queue->method('getName')->willReturn('default');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $decorator = new QueueDecorator($queue, $collector);
        $decorator->push($message);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['queue']['countPushes']);
    }
}
