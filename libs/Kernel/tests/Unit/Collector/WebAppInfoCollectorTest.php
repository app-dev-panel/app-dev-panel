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
    }
}
