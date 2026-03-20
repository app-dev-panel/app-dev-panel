<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use function count;

/**
 * Captures view/template rendering with output and parameters.
 *
 * Framework adapters call collectRender() with normalized data
 * from their view/template rendering system.
 */
final class ViewCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{file: string, output: string, parameters: array}> */
    private array $renders = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collectRender(string $file, string $output = '', array $parameters = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->renders[] = [
            'output' => $output,
            'file' => $file,
            'parameters' => $parameters,
        ];
        $this->timelineCollector->collect($this, count($this->renders));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return $this->renders;
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'view' => [
                'total' => count($this->renders),
            ],
        ];
    }

    protected function reset(): void
    {
        $this->renders = [];
    }
}
