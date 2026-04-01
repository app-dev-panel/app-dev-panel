<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\View;

use AppDevPanel\Adapter\Yii3\Collector\View\ViewEventListener;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class ViewEventListenerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Yiisoft\View\Event\WebView\AfterRender::class, true)) {
            $this->markTestSkipped('yiisoft/view is not installed.');
        }
    }

    public function testCollectDelegatesRenderDataToTemplateCollector(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();
        $listener = new ViewEventListener($collector);

        $event = $this->createMock(\Yiisoft\View\Event\WebView\AfterRender::class);
        $event->method('getFile')->willReturn('/views/index.php');
        $event->method('getResult')->willReturn('<h1>Hello</h1>');
        $event->method('getParameters')->willReturn(['title' => 'Test']);

        $listener->collect($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['renders']);
        $this->assertSame('/views/index.php', $collected['renders'][0]['template']);
        $this->assertSame('<h1>Hello</h1>', $collected['renders'][0]['output']);
        $this->assertSame(['title' => 'Test'], $collected['renders'][0]['parameters']);
    }

    public function testCollectMultipleRenderEvents(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();
        $listener = new ViewEventListener($collector);

        $event1 = $this->createMock(\Yiisoft\View\Event\WebView\AfterRender::class);
        $event1->method('getFile')->willReturn('/views/layout.php');
        $event1->method('getResult')->willReturn('<html></html>');
        $event1->method('getParameters')->willReturn([]);

        $event2 = $this->createMock(\Yiisoft\View\Event\WebView\AfterRender::class);
        $event2->method('getFile')->willReturn('/views/partial.php');
        $event2->method('getResult')->willReturn('<div>Partial</div>');
        $event2->method('getParameters')->willReturn(['key' => 'value']);

        $listener->collect($event1);
        $listener->collect($event2);

        $collected = $collector->getCollected();
        $this->assertCount(2, $collected['renders']);
        $this->assertSame('/views/layout.php', $collected['renders'][0]['template']);
        $this->assertSame('/views/partial.php', $collected['renders'][1]['template']);
    }

    public function testCollectUpdatesTimeline(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();
        $listener = new ViewEventListener($collector);

        $event = $this->createMock(\Yiisoft\View\Event\WebView\AfterRender::class);
        $event->method('getFile')->willReturn('/views/test.php');
        $event->method('getResult')->willReturn('');
        $event->method('getParameters')->willReturn([]);

        $listener->collect($event);

        $this->assertCount(1, $timeline->getCollected());
    }

    public function testCollectUpdatesSummary(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new TemplateCollector($timeline);
        $collector->startup();
        $listener = new ViewEventListener($collector);

        $event = $this->createMock(\Yiisoft\View\Event\WebView\AfterRender::class);
        $event->method('getFile')->willReturn('/views/test.php');
        $event->method('getResult')->willReturn('output');
        $event->method('getParameters')->willReturn([]);

        $listener->collect($event);

        $summary = $collector->getSummary();
        $this->assertSame(1, $summary['template']['renderCount']);
        $this->assertSame(0.0, $summary['template']['totalTime']);
    }
}
