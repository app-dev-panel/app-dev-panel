<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\View;

use AppDevPanel\Adapter\Yii3\Collector\View\ViewEventListener;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\WebView;

final class ViewEventListenerTest extends TestCase
{
    private function buildListener(): array
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();
        return [new ViewEventListener($collector), $collector, $timeline];
    }

    public function testBeforeAfterRenderCollectsTemplateData(): void
    {
        [$listener, $collector] = $this->buildListener();
        $view = new WebView();

        $listener->beforeRender(new BeforeRender($view, '/views/index.php', ['title' => 'Test']));
        $listener->afterRender(new AfterRender($view, '/views/index.php', ['title' => 'Test'], '<h1>Hello</h1>'));

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['renders']);
        $this->assertSame('/views/index.php', $collected['renders'][0]['template']);
        $this->assertSame('<h1>Hello</h1>', $collected['renders'][0]['output']);
        $this->assertSame(['title' => 'Test'], $collected['renders'][0]['parameters']);
    }

    public function testRenderTimeIsMeasured(): void
    {
        [$listener, $collector] = $this->buildListener();
        $view = new WebView();

        $listener->beforeRender(new BeforeRender($view, '/views/slow.php', []));
        usleep(2000);
        $listener->afterRender(new AfterRender($view, '/views/slow.php', [], 'out'));

        $collected = $collector->getCollected();
        $this->assertGreaterThan(0.0, $collected['renders'][0]['renderTime']);
        $this->assertGreaterThan(0.0, $collected['totalTime']);
        $this->assertSame(1, $collected['renderCount']);
    }

    public function testMultipleSequentialRenders(): void
    {
        [$listener, $collector] = $this->buildListener();
        $view = new WebView();

        $listener->beforeRender(new BeforeRender($view, '/views/layout.php', []));
        $listener->afterRender(new AfterRender($view, '/views/layout.php', [], '<html></html>'));

        $listener->beforeRender(new BeforeRender($view, '/views/partial.php', ['key' => 'value']));
        $listener->afterRender(new AfterRender($view, '/views/partial.php', ['key' => 'value'], '<div>Partial</div>'));

        $collected = $collector->getCollected();
        $this->assertCount(2, $collected['renders']);
        $this->assertSame('/views/layout.php', $collected['renders'][0]['template']);
        $this->assertSame('/views/partial.php', $collected['renders'][1]['template']);
        $this->assertSame(0, $collected['renders'][0]['depth']);
        $this->assertSame(0, $collected['renders'][1]['depth']);
    }

    public function testNestedRendersTrackDepthAndOrdering(): void
    {
        [$listener, $collector] = $this->buildListener();
        $view = new WebView();

        // Outer layout starts rendering, then includes a partial, then finishes.
        $listener->beforeRender(new BeforeRender($view, '/views/layout.php', []));
        $listener->beforeRender(new BeforeRender($view, '/views/partial.php', []));
        $listener->afterRender(new AfterRender($view, '/views/partial.php', [], '<div>Partial</div>'));
        $listener->afterRender(new AfterRender($view, '/views/layout.php', [], '<html><div>Partial</div></html>'));

        $collected = $collector->getCollected();
        $this->assertCount(2, $collected['renders']);
        // Parent-first ordering from beginRender.
        $this->assertSame('/views/layout.php', $collected['renders'][0]['template']);
        $this->assertSame(0, $collected['renders'][0]['depth']);
        $this->assertSame('/views/partial.php', $collected['renders'][1]['template']);
        $this->assertSame(1, $collected['renders'][1]['depth']);
    }

    public function testRenderUpdatesTimeline(): void
    {
        [$listener, $collector, $timeline] = $this->buildListener();
        $view = new WebView();

        $listener->beforeRender(new BeforeRender($view, '/views/test.php', []));
        $listener->afterRender(new AfterRender($view, '/views/test.php', [], ''));

        $this->assertCount(1, $timeline->getCollected());
    }

    public function testSummaryReflectsRenders(): void
    {
        [$listener, $collector] = $this->buildListener();
        $view = new WebView();

        $listener->beforeRender(new BeforeRender($view, '/views/test.php', []));
        $listener->afterRender(new AfterRender($view, '/views/test.php', [], 'output'));

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['template']['renderCount']);
        $this->assertGreaterThanOrEqual(0.0, $summary['template']['totalTime']);
    }
}
