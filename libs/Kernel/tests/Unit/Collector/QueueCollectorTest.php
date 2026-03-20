<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class QueueCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new QueueCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof QueueCollector);
        $collector->collectPush('default', ['class' => 'SendEmail', 'data' => ['to' => 'test@example.com']]);
        $collector->collectPush('default', ['class' => 'ProcessPayment', 'data' => ['amount' => 100]]);
        $collector->collectStatus('job-1', 'completed');
        $collector->collectStatus('job-2', 'pending');
        $collector->collectWorkerProcessing(['class' => 'SendEmail'], 'default');
        $collector->logMessage(
            messageClass: 'App\\Message\\SendNotification',
            bus: 'messenger.bus.default',
            transport: 'async',
            dispatched: true,
            handled: true,
            failed: false,
            duration: 0.025,
        );
        $collector->logMessage(
            messageClass: 'App\\Message\\ProcessPayment',
            bus: 'messenger.bus.default',
            transport: 'async',
            dispatched: true,
            handled: false,
            failed: true,
            duration: 0.100,
        );
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('pushes', $data);
        $this->assertCount(2, $data['pushes']['default']);

        $this->assertArrayHasKey('statuses', $data);
        $this->assertCount(2, $data['statuses']);
        $this->assertSame('completed', $data['statuses'][0]['status']);

        $this->assertArrayHasKey('processingMessages', $data);
        $this->assertCount(1, $data['processingMessages']['default']);

        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(2, $data['messages']);
        $this->assertSame(2, $data['messageCount']);
        $this->assertSame(1, $data['failedCount']);

        $msg = $data['messages'][0];
        $this->assertSame('App\\Message\\SendNotification', $msg['messageClass']);
        $this->assertSame('messenger.bus.default', $msg['bus']);
        $this->assertSame('async', $msg['transport']);
        $this->assertTrue($msg['dispatched']);
        $this->assertTrue($msg['handled']);
        $this->assertFalse($msg['failed']);

        $this->assertTrue($data['messages'][1]['failed']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('queue', $data);
        $this->assertSame(2, $data['queue']['countPushes']);
        $this->assertSame(2, $data['queue']['countStatuses']);
        $this->assertSame(1, $data['queue']['countProcessingMessages']);
        $this->assertSame(2, $data['queue']['messageCount']);
        $this->assertSame(1, $data['queue']['failedCount']);
    }
}
