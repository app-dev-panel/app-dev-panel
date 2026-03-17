<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\TwigCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class TwigCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new TwigCollector(new TimelineCollector());
    }

    /**
     * @param CollectorInterface|TwigCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
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

        $this->assertArrayHasKey('twig', $data);
        $this->assertSame(3, $data['twig']['renderCount']);
        $this->assertSame(0.023, $data['twig']['totalTime']);
    }
}
