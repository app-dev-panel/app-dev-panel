<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\View;

use AppDevPanel\Kernel\Collector\TemplateCollector;
use Yiisoft\View\Event\WebView\AfterRender;

/**
 * Listens to Yii view render events and feeds normalized data
 * to the framework-agnostic Kernel TemplateCollector.
 */
final class ViewEventListener
{
    public function __construct(
        private readonly TemplateCollector $collector,
    ) {}

    public function collect(AfterRender $event): void
    {
        $this->collector->collectRender($event->getFile(), $event->getResult(), $event->getParameters());
    }
}
