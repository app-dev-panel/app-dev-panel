<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Queue;

use AppDevPanel\Adapter\Yii3\Collector\Queue\QueueWorkerInterfaceProxy;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class QueueWorkerInterfaceProxyTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Queue\Worker\WorkerInterface::class, true)) {
            $this->markTestSkipped('yiisoft/queue is not installed.');
        }
    }

    public function testProcessDelegatesToDecoratedAndCollects(): void
    {
        $message = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);
        $processedMessage = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->method('getName')->willReturn('notifications');

        $worker = $this->createMock(\Yiisoft\Queue\Worker\WorkerInterface::class);
        $worker->expects($this->once())->method('process')->with($message, $queue)->willReturn($processedMessage);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $proxy = new QueueWorkerInterfaceProxy($worker, $collector);
        $result = $proxy->process($message, $queue);

        $this->assertSame($processedMessage, $result);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['processingMessages']['notifications']);
        $this->assertSame($message, $collected['processingMessages']['notifications'][0]);
    }

    public function testProcessCollectsQueueName(): void
    {
        $message = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);
        $queue1 = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue1->method('getName')->willReturn('emails');
        $queue2 = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue2->method('getName')->willReturn('sms');

        $worker = $this->createMock(\Yiisoft\Queue\Worker\WorkerInterface::class);
        $worker->method('process')->willReturn($message);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $proxy = new QueueWorkerInterfaceProxy($worker, $collector);
        $proxy->process($message, $queue1);
        $proxy->process($message, $queue2);

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('emails', $collected['processingMessages']);
        $this->assertArrayHasKey('sms', $collected['processingMessages']);
    }

    public function testProcessUpdatesSummary(): void
    {
        $message = $this->createMock(\Yiisoft\Queue\Message\MessageInterface::class);
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);
        $queue->method('getName')->willReturn('default');

        $worker = $this->createMock(\Yiisoft\Queue\Worker\WorkerInterface::class);
        $worker->method('process')->willReturn($message);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new QueueCollector($timeline);
        $collector->startup();

        $proxy = new QueueWorkerInterfaceProxy($worker, $collector);
        $proxy->process($message, $queue);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['queue']['countProcessingMessages']);
    }
}
