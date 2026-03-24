<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ViewCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class ViewCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new ViewCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof ViewCollector, 'Expected ViewCollector instance');
        $collector->collectRender('/views/layout.php', '<html>...</html>', ['title' => 'Home']);
        $collector->collectRender('/views/index.php', '<div>content</div>', ['items' => [1, 2, 3]]);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('renders', $data);
        $this->assertArrayHasKey('duplicates', $data);
        $this->assertCount(2, $data['renders']);
        $this->assertSame('/views/layout.php', $data['renders'][0]['file']);
        $this->assertSame('<html>...</html>', $data['renders'][0]['output']);
        $this->assertSame(['title' => 'Home'], $data['renders'][0]['parameters']);

        $this->assertSame([], $data['duplicates']['groups']);
        $this->assertSame(0, $data['duplicates']['totalDuplicatedCount']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('view', $data);
        $this->assertSame(2, $data['view']['total']);
    }
}
