<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Console;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

final class ConsoleAppInfoCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private float $applicationProcessingTimeStarted = 0;
    private float $applicationProcessingTimeStopped = 0;
    private float $requestProcessingTimeStarted = 0;
    private float $requestProcessingTimeStopped = 0;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
        private readonly string $adapterName = '',
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'applicationProcessingTime' =>
                $this->applicationProcessingTimeStopped - $this->applicationProcessingTimeStarted,
            'preloadTime' => $this->applicationProcessingTimeStarted - $this->requestProcessingTimeStarted,
            'applicationEmit' => $this->applicationProcessingTimeStopped - $this->requestProcessingTimeStopped,
            'requestProcessingTime' => $this->requestProcessingTimeStopped - $this->requestProcessingTimeStarted,
            'memoryPeakUsage' => memory_get_peak_usage(),
            'memoryUsage' => memory_get_usage(),
            'adapter' => $this->adapterName,
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

    public function markApplicationFinished(): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->applicationProcessingTimeStopped = microtime(true);
        $this->timelineCollector->collect($this, 'app-finish');
    }

    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($event instanceof ConsoleCommandEvent) {
            $this->requestProcessingTimeStarted = microtime(true);
        } elseif ($event instanceof ConsoleErrorEvent) {
            $this->requestProcessingTimeStarted = $this->applicationProcessingTimeStarted;
            $this->requestProcessingTimeStopped = microtime(true);
        } elseif ($event instanceof ConsoleTerminateEvent) {
            $this->requestProcessingTimeStopped = microtime(true);
        }

        $this->timelineCollector->collect($this, spl_object_id($event));
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'console' => [
                'adapter' => $this->adapterName,
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
    }
}
