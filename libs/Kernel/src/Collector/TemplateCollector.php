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
 * - beginRender()/endRender() pair for nested rendering with hierarchy tracking
 *
 * Works with any template/view engine. Includes duplicate detection for N+1 rendering issues.
 */
final class TemplateCollector implements SummaryCollectorInterface
{
    use CollectorTrait;
    use DuplicateDetectionTrait;

    /** @var array<int, array{template: string, renderTime: float, output: string, parameters: array, depth: int}> */
    private array $renders = [];
    private float $totalTime = 0.0;
    private int $currentDepth = 0;

    /** @var int[] Stack of render indices for pending beginRender/endRender pairs */
    private array $pendingStack = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Signal that a template render is starting. Creates a placeholder entry at the current depth.
     * Pair with endRender() after the render completes to fill in output, params, and timing.
     * This gives parent-first ordering in the renders array, which is needed for hierarchy display.
     */
    public function beginRender(string $template): void
    {
        if (!$this->isActive()) {
            return;
        }

        $index = count($this->renders);
        $this->renders[] = [
            'template' => $template,
            'renderTime' => 0.0,
            'output' => '',
            'parameters' => [],
            'depth' => $this->currentDepth,
        ];
        $this->pendingStack[] = $index;
        $this->currentDepth++;
        $this->timelineCollector->collect($this, $index + 1);
    }

    /**
     * Signal that a template render has finished. Fills in the pending entry with output, params, and timing.
     */
    public function endRender(string $output = '', array $parameters = [], float $renderTime = 0.0): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($this->currentDepth > 0) {
            $this->currentDepth--;
        }

        if ($this->pendingStack !== []) {
            $index = array_pop($this->pendingStack);
            $this->renders[$index]['output'] = $output;
            $this->renders[$index]['parameters'] = $parameters;
            $this->renders[$index]['renderTime'] = $renderTime;
            $this->totalTime += $renderTime;
        }
    }

    /**
     * Log a template render with timing data (e.g. Twig, Blade).
     */
    public function logRender(string $template, float $renderTime = 0.0): void
    {
        $this->collectRender($template, '', [], $renderTime);
    }

    /**
     * Collect a template/view render with optional output, parameters, timing, and depth.
     * Use this for simple (non-nested) collection. For nested rendering, use beginRender/endRender.
     */
    public function collectRender(
        string $template,
        string $output = '',
        array $parameters = [],
        float $renderTime = 0.0,
        int $depth = -1,
    ): void {
        if (!$this->isActive()) {
            return;
        }

        $this->renders[] = [
            'template' => $template,
            'renderTime' => $renderTime,
            'output' => $output,
            'parameters' => $parameters,
            'depth' => $depth >= 0 ? $depth : $this->currentDepth,
        ];
        $this->totalTime += $renderTime;

        $this->timelineCollector->collect($this, count($this->renders));
    }

    public function getCollected(): array
    {
        return [
            'renders' => $this->renders,
            'totalTime' => $this->totalTime,
            'renderCount' => count($this->renders),
            'duplicates' => $this->detectDuplicates($this->renders, static fn(array $render) => $render['template']),
        ];
    }

    public function getSummary(): array
    {
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
        $this->currentDepth = 0;
        $this->pendingStack = [];
    }
}
