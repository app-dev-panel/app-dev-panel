<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\MessageBusCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class MessageBusCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MessageBusCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof MessageBusCollector);
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

        $this->assertSame(2, $data['messageCount']);
        $this->assertSame(1, $data['failedCount']);
        $this->assertCount(2, $data['messages']);

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

        $this->assertArrayHasKey('messageBus', $data);
        $this->assertSame(2, $data['messageBus']['messageCount']);
        $this->assertSame(1, $data['messageBus']['failedCount']);
    }
}
