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
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('queue', $data);
        $this->assertSame(2, $data['queue']['countPushes']);
        $this->assertSame(2, $data['queue']['countStatuses']);
        $this->assertSame(1, $data['queue']['countProcessingMessages']);
    }
}
