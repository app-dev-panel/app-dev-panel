<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\View;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use Yiisoft\View\Event\WebView\AfterRender;

use function count;

final class WebViewCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $renders = [];

    public function getCollected(): array
    {
        return $this->renders;
    }

    public function collect(AfterRender $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->renders[] = [
            'output' => $event->getResult(),
            'file' => $event->getFile(),
            'parameters' => $event->getParameters(),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'total' => count($this->renders),
        ];
    }

    private function reset(): void
    {
        $this->renders = [];
    }
}
