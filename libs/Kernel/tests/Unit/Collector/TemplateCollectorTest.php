<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class TemplateCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new TemplateCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof TemplateCollector);
        $collector->logRender('base.html.twig', 0.012);
        $collector->logRender('pages/home.html.twig', 0.008);
        $collector->logRender('components/navbar.html.twig', 0.003);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame(3, $data['renderCount']);
        $this->assertSame(0.023, $data['totalTime']);
        $this->assertCount(3, $data['renders']);

        $this->assertSame('base.html.twig', $data['renders'][0]['template']);
        $this->assertSame(0.012, $data['renders'][0]['renderTime']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('template', $data);
        $this->assertSame(3, $data['template']['renderCount']);
        $this->assertSame(0.023, $data['template']['totalTime']);
    }
}
