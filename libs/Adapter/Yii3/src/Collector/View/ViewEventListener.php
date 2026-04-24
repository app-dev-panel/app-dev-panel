<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\View;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;

/**
 * Listens to Yii view render events and feeds normalized data
 * to the framework-agnostic Kernel TemplateCollector.
 *
 * BeforeRender opens an entry on the collector's nesting stack and records a
 * microtime snapshot; AfterRender pops it and reports the elapsed render time
 * along with the produced output and parameters. Because yiisoft/view renders
 * are strictly nested, a plain LIFO stack of start times is sufficient.
 */
final class ViewEventListener
{
    /** @var float[] */
    private array $startStack = [];

    public function __construct(
        private readonly TemplateCollector $collector,
    ) {}

    public function beforeRender(BeforeRender $event): void
    {
        $this->collector->beginRender($event->getFile());
        $this->startStack[] = microtime(true);
    }

    public function afterRender(AfterRender $event): void
    {
        $start = array_pop($this->startStack);
        $renderTime = $start !== null ? microtime(true) - $start : 0.0;

        $this->collector->endRender($event->getResult(), $event->getParameters(), $renderTime);
    }
}
