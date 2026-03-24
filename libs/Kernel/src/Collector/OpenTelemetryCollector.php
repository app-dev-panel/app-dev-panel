<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class OpenTelemetryCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var list<array<string, mixed>> */
    private array $spans = [];

    private int $errorCount = 0;

    /** @var array<string, true> */
    private array $traceIds = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collect(SpanRecord $span): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->spans[] = $span->toArray();
        $this->traceIds[$span->traceId] = true;

        if ($span->status === 'ERROR') {
            ++$this->errorCount;
        }

        $this->timelineCollector->collect($this, count($this->spans));
    }

    /**
     * @param list<SpanRecord> $spans
     */
    public function collectBatch(array $spans): void
    {
        foreach ($spans as $span) {
            $this->collect($span);
        }
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'spans' => $this->spans,
            'traceCount' => count($this->traceIds),
            'spanCount' => count($this->spans),
            'errorCount' => $this->errorCount,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'opentelemetry' => [
                'spans' => count($this->spans),
                'traces' => count($this->traceIds),
                'errors' => $this->errorCount,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->spans = [];
        $this->traceIds = [];
        $this->errorCount = 0;
    }
}
