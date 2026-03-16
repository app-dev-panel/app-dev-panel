<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

final class WebAppInfoCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private float $applicationProcessingTimeStarted = 0;
    private float $applicationProcessingTimeStopped = 0;
    private float $requestProcessingTimeStarted = 0;
    private float $requestProcessingTimeStopped = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'applicationProcessingTime' =>
                $this->applicationProcessingTimeStopped - $this->applicationProcessingTimeStarted,
            'requestProcessingTime' => $this->requestProcessingTimeStopped - $this->requestProcessingTimeStarted,
            'applicationEmit' => $this->applicationProcessingTimeStopped - $this->requestProcessingTimeStopped,
            'preloadTime' => $this->requestProcessingTimeStarted - $this->applicationProcessingTimeStarted,
            'memoryPeakUsage' => memory_get_peak_usage(),
            'memoryUsage' => memory_get_usage(),
        ];
    }

    public function markApplicationStarted(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->applicationProcessingTimeStarted = microtime(true);
        $this->timelineCollector->collect($this, 'app-start');
    }

    public function markRequestStarted(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->requestProcessingTimeStarted = microtime(true);
        $this->timelineCollector->collect($this, 'request-start');
    }

    public function markRequestFinished(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->requestProcessingTimeStopped = microtime(true);
        $this->timelineCollector->collect($this, 'request-finish');
    }

    public function markApplicationFinished(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->applicationProcessingTimeStopped = microtime(true);
        $this->timelineCollector->collect($this, 'app-finish');
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'web' => [
                'php' => [
                    'version' => PHP_VERSION,
                ],
                'request' => [
                    'startTime' => $this->requestProcessingTimeStarted,
                    'processingTime' => $this->requestProcessingTimeStopped - $this->requestProcessingTimeStarted,
                ],
                'memory' => [
                    'peakUsage' => memory_get_peak_usage(),
                ],
            ],
        ];
    }

    private function reset(): void
    {
        $this->applicationProcessingTimeStarted = 0;
        $this->applicationProcessingTimeStopped = 0;
        $this->requestProcessingTimeStarted = 0;
        $this->requestProcessingTimeStopped = 0;
    }
}
