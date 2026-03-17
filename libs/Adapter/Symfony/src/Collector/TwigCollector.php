<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Collects Twig template rendering data.
 *
 * Captures:
 * - Template names rendered
 * - Render time per template
 * - Total render count and cumulative time
 *
 * Data is fed via `logRender()` method, called from a Twig profiler extension.
 */
final class TwigCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{template: string, renderTime: float}> */
    private array $renders = [];
    private float $totalTime = 0.0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function logRender(string $template, float $renderTime = 0.0): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->renders[] = [
            'template' => $template,
            'renderTime' => $renderTime,
        ];
        $this->totalTime += $renderTime;

        $this->timelineCollector->collect($this, count($this->renders));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'renders' => $this->renders,
            'totalTime' => $this->totalTime,
            'renderCount' => count($this->renders),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'twig' => [
                'renderCount' => count($this->renders),
                'totalTime' => $this->totalTime,
            ],
        ];
    }

    private function reset(): void
    {
        $this->renders = [];
        $this->totalTime = 0.0;
    }
}
