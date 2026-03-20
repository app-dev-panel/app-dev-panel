<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures template rendering data with timing.
 *
 * Framework adapters call logRender() with template name and render time.
 * Works with any template engine (Twig, Blade, Plates, etc.).
 */
final class TemplateCollector implements SummaryCollectorInterface
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
            'template' => [
                'renderCount' => count($this->renders),
                'totalTime' => $this->totalTime,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->renders = [];
        $this->totalTime = 0.0;
    }
}
