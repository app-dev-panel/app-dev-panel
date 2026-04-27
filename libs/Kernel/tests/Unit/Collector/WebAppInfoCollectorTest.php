<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

use function sleep;
use function usleep;

final class WebAppInfoCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|WebAppInfoCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->markRequestStarted();

        DIRECTORY_SEPARATOR === '\\' ? sleep(1) : usleep(123_000);

        $collector->markRequestFinished();
    }

    protected function getCollector(): CollectorInterface
    {
        return new WebAppInfoCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertGreaterThan(0.122, $data['requestProcessingTime']);
        $this->assertSame('', $data['adapter']);
    }

    public function testAdapterName(): void
    {
        $collector = new WebAppInfoCollector(new TimelineCollector(), 'Symfony');
        $collector->startup();
        $collector->markRequestStarted();
        $collector->markRequestFinished();

        $collected = $collector->getCollected();
        $this->assertSame('Symfony', $collected['adapter']);

        $summary = $collector->getSummary();
        $this->assertIsArray($summary['web']);
        $this->assertSame('Symfony', $summary['web']['adapter']);
    }

    public function testAdapterNameDefaultsToEmpty(): void
    {
        $collector = new WebAppInfoCollector(new TimelineCollector());
        $collector->startup();
        $collector->markRequestStarted();
        $collector->markRequestFinished();

        $collected = $collector->getCollected();
        $this->assertSame('', $collected['adapter']);

        $summary = $collector->getSummary();
        $this->assertIsArray($summary['web']);
        $this->assertSame('', $summary['web']['adapter']);
    }

    public function testMarkApplicationStartedAndFinished(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new WebAppInfoCollector($timeline);
        $collector->startup();

        $collector->markApplicationStarted();
        $collector->markRequestStarted();

        usleep(10_000);

        $collector->markRequestFinished();
        $collector->markApplicationFinished();

        $collected = $collector->getCollected();

        $this->assertGreaterThan(0, $collected['applicationProcessingTime']);
        $this->assertGreaterThan(0, $collected['requestProcessingTime']);
        $this->assertGreaterThanOrEqual(0, $collected['applicationEmit']);
        $this->assertGreaterThanOrEqual(0, $collected['preloadTime']);
    }

    public function testMarkApplicationStartedWhenInactive(): void
    {
        $collector = new WebAppInfoCollector(new TimelineCollector());
        // Not started — mark*() must be a no-op. We can't compare full
        // getCollected() because memoryPeakUsage / memoryUsage drift between
        // calls; assert only the timing fields the mark*() methods mutate.
        $collector->markApplicationStarted();
        $collector->markApplicationFinished();

        $data = $collector->getCollected();
        $this->assertSame(0.0, $data['applicationProcessingTime']);
        $this->assertSame(0.0, $data['requestProcessingTime']);
    }
}
