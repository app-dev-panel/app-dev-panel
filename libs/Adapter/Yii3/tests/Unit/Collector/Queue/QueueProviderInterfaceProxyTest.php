<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Queue;

use AppDevPanel\Adapter\Yii3\Collector\Queue\QueueDecorator;
use AppDevPanel\Adapter\Yii3\Collector\Queue\QueueProviderInterfaceProxy;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class QueueProviderInterfaceProxyTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Queue\Provider\QueueProviderInterface::class, true)) {
            $this->markTestSkipped('yiisoft/queue is not installed.');
        }
    }

    public function testGetReturnsQueueDecorator(): void
    {
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);

        $provider = $this->createMock(\Yiisoft\Queue\Provider\QueueProviderInterface::class);
        $provider->expects($this->once())->method('get')->with('default')->willReturn($queue);

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $proxy = new QueueProviderInterfaceProxy($provider, $collector);
        $result = $proxy->get('default');

        $this->assertInstanceOf(QueueDecorator::class, $result);
    }

    public function testHasDelegatesToDecorated(): void
    {
        $provider = $this->createMock(\Yiisoft\Queue\Provider\QueueProviderInterface::class);
        $provider
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(fn(string $name) => $name === 'default');

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $proxy = new QueueProviderInterfaceProxy($provider, $collector);

        $this->assertTrue($proxy->has('default'));
        $this->assertFalse($proxy->has('nonexistent'));
    }

    public function testGetWithDifferentQueueName(): void
    {
        $queue = $this->createMock(\Yiisoft\Queue\QueueInterface::class);

        $provider = $this->createMock(\Yiisoft\Queue\Provider\QueueProviderInterface::class);
        $provider->method('get')->willReturn($queue);

        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);

        $proxy = new QueueProviderInterfaceProxy($provider, $collector);
        $result = $proxy->get('email-queue');

        $this->assertInstanceOf(QueueDecorator::class, $result);
    }
}
