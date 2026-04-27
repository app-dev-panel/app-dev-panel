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
        assert($collector instanceof TemplateCollector, 'Expected TemplateCollector instance');
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
        $this->assertSame('', $data['renders'][0]['output']);
        $this->assertSame([], $data['renders'][0]['parameters']);
        $this->assertSame(0, $data['renders'][0]['depth']);

        $this->assertArrayHasKey('duplicates', $data);
        $this->assertSame([], $data['duplicates']['groups']);
        $this->assertSame(0, $data['duplicates']['totalDuplicatedCount']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('template', $data);
        $this->assertSame(3, $data['template']['renderCount']);
        $this->assertSame(0.023, $data['template']['totalTime']);
        $this->assertSame(0, $data['template']['duplicateGroups']);
        $this->assertSame(0, $data['template']['totalDuplicatedCount']);
    }

    public function testCollectRenderWithOutputAndParameters(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();

        $collector->collectRender('/views/layout.php', '<html>...</html>', ['title' => 'Home']);
        $collector->collectRender('/views/index.php', '<div>content</div>', ['items' => [1, 2, 3]]);

        $data = $collector->getCollected();

        $this->assertCount(2, $data['renders']);
        $this->assertSame('/views/layout.php', $data['renders'][0]['template']);
        $this->assertSame('<html>...</html>', $data['renders'][0]['output']);
        $this->assertSame(['title' => 'Home'], $data['renders'][0]['parameters']);
        $this->assertSame(0.0, $data['renders'][0]['renderTime']);

        $this->assertSame(2, $data['renderCount']);
        $this->assertSame(0.0, $data['totalTime']);
    }

    public function testCollectRenderWithAllFields(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();

        $collector->collectRender('home/index.html.twig', '<h1>Hello</h1>', ['name' => 'World'], 0.005);

        $data = $collector->getCollected();

        $this->assertSame('home/index.html.twig', $data['renders'][0]['template']);
        $this->assertSame('<h1>Hello</h1>', $data['renders'][0]['output']);
        $this->assertSame(['name' => 'World'], $data['renders'][0]['parameters']);
        $this->assertSame(0.005, $data['renders'][0]['renderTime']);
        $this->assertSame(0.005, $data['totalTime']);
    }

    public function testBeginEndRenderNesting(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();

        // Simulate: layout > view-with-partials > _sidebar, _content-block
        $collector->beginRender('/views/layout.php'); // depth 0
        $collector->beginRender('/views/view-with-partials.php'); // depth 1
        $collector->beginRender('/views/_sidebar.php'); // depth 2
        $collector->endRender('<nav>...</nav>', [], 0.003);
        $collector->beginRender('/views/_content-block.php'); // depth 2
        $collector->endRender('<section>...</section>', [], 0.002);
        $collector->endRender('<div>...</div>', ['title' => 'Test'], 0.010);
        $collector->endRender('<html>...</html>', ['content' => '...'], 0.015);

        $data = $collector->getCollected();

        // Parent-first order: layout, view-with-partials, _sidebar, _content-block
        $this->assertCount(4, $data['renders']);

        $this->assertSame('/views/layout.php', $data['renders'][0]['template']);
        $this->assertSame(0, $data['renders'][0]['depth']);
        $this->assertSame('<html>...</html>', $data['renders'][0]['output']);
        $this->assertSame(0.015, $data['renders'][0]['renderTime']);

        $this->assertSame('/views/view-with-partials.php', $data['renders'][1]['template']);
        $this->assertSame(1, $data['renders'][1]['depth']);
        $this->assertSame(['title' => 'Test'], $data['renders'][1]['parameters']);

        $this->assertSame('/views/_sidebar.php', $data['renders'][2]['template']);
        $this->assertSame(2, $data['renders'][2]['depth']);

        $this->assertSame('/views/_content-block.php', $data['renders'][3]['template']);
        $this->assertSame(2, $data['renders'][3]['depth']);

        $this->assertSame(0.030, $data['totalTime']);
    }

    public function testExplicitDepthParameter(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();

        $collector->collectRender('layout.twig', '', [], 0.0, 0);
        $collector->collectRender('page.twig', '', [], 0.0, 1);
        $collector->collectRender('widget.twig', '', [], 0.0, 2);

        $data = $collector->getCollected();

        $this->assertSame(0, $data['renders'][0]['depth']);
        $this->assertSame(1, $data['renders'][1]['depth']);
        $this->assertSame(2, $data['renders'][2]['depth']);
    }

    public function testBeginRenderWhenInactive(): void
    {
        $collector = new TemplateCollector(new TimelineCollector());
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        // Not started — should be no-op
        $collector->beginRender('layout.twig');

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testEndRenderWhenInactive(): void
    {
        $collector = new TemplateCollector(new TimelineCollector());
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        // Not started — should be no-op
        $collector->endRender('<html>test</html>');

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testDuplicateDetection(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();

        $collector->collectRender('/views/partial.php', '<p>1</p>');
        $collector->collectRender('/views/partial.php', '<p>2</p>');
        $collector->collectRender('/views/partial.php', '<p>3</p>');
        $collector->collectRender('/views/other.php', '<p>other</p>');

        $data = $collector->getCollected();

        $this->assertCount(1, $data['duplicates']['groups']);
        $this->assertSame('/views/partial.php', $data['duplicates']['groups'][0]['key']);
        $this->assertSame(3, $data['duplicates']['groups'][0]['count']);
        $this->assertSame(3, $data['duplicates']['totalDuplicatedCount']);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['template']['duplicateGroups']);
        $this->assertSame(3, $summary['template']['totalDuplicatedCount']);
    }
}
