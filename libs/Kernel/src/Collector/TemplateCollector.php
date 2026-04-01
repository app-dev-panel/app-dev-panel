<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use function count;

/**
 * Captures template/view rendering with optional timing, output, and parameters.
 *
 * Framework adapters call either:
 * - logRender() for template engines with timing (Twig, Blade)
 * - collectRender() for view systems with output capture (Yii views, PHP templates)
 *
 * Works with any template/view engine. Includes duplicate detection for N+1 rendering issues.
 */
final class TemplateCollector implements SummaryCollectorInterface
{
    use CollectorTrait;
    use DuplicateDetectionTrait;

    /** @var array<int, array{template: string, renderTime: float, output: string, parameters: array}> */
    private array $renders = [];
    private float $totalTime = 0.0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Log a template render with timing data (e.g. Twig, Blade).
     */
    public function logRender(string $template, float $renderTime = 0.0): void
    {
        $this->collectRender($template, '', [], $renderTime);
    }

    /**
     * Collect a template/view render with optional output, parameters, and timing.
     */
    public function collectRender(
        string $template,
        string $output = '',
        array $parameters = [],
        float $renderTime = 0.0,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->renders[] = [
            'template' => $template,
            'renderTime' => $renderTime,
            'output' => $output,
            'parameters' => $parameters,
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
            'duplicates' => $this->detectDuplicates($this->renders, static fn(array $render) => $render['template']),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $duplicates = $this->detectDuplicates($this->renders, static fn(array $render) => $render['template']);

        return [
            'template' => [
                'renderCount' => count($this->renders),
                'totalTime' => $this->totalTime,
                'duplicateGroups' => count($duplicates['groups']),
                'totalDuplicatedCount' => $duplicates['totalDuplicatedCount'],
            ],
        ];
    }

    protected function reset(): void
    {
        $this->renders = [];
        $this->totalTime = 0.0;
    }
}
