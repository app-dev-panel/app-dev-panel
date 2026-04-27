<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class VarDumperCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $vars = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collect(mixed $variable, string $line): void
    {
        $this->vars[] = [
            'variable' => $variable,
            'line' => $line,
        ];
        $this->timelineCollector->collect($this, count($this->vars));
    }

    public function getCollected(): array
    {
        return $this->vars;
    }

    public function getSummary(): array
    {
        return [
            'var-dumper' => [
                'total' => count($this->vars),
            ],
        ];
    }
}
