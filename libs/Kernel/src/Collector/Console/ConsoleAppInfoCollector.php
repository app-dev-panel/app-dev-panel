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

    public const EVENT_APPLICATION_STARTUP = 'console.app.startup';
    public const EVENT_APPLICATION_SHUTDOWN = 'console.app.shutdown';

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
            'preloadTime' => $this->applicationProcessingTimeStarted - $this->requestProcessingTimeStarted,
            'applicationEmit' => $this->applicationProcessingTimeStopped - $this->requestProcessingTimeStopped,
            'requestProcessingTime' => $this->requestProcessingTimeStopped - $this->requestProcessingTimeStarted,
            'memoryPeakUsage' => memory_get_peak_usage(),
            'memoryUsage' => memory_get_usage(),
        ];
    }

    /**
     * Collect timing data based on a string event type or Symfony Console event.
     */
    public function collectTiming(string $eventType): void
    {
        if (!$this->isActive()) {
            return;
        }

        match ($eventType) {
            self::EVENT_APPLICATION_STARTUP => $this->applicationProcessingTimeStarted = microtime(true),
            self::EVENT_APPLICATION_SHUTDOWN => $this->applicationProcessingTimeStopped = microtime(true),
            default => null,
        };
        $this->timelineCollector->collect($this, crc32($eventType));
    }

    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        $className = $event::class;

        // Map known Symfony Console events
        if ($event instanceof ConsoleCommandEvent) {
            $this->requestProcessingTimeStarted = microtime(true);
        } elseif ($event instanceof ConsoleErrorEvent) {
            $this->requestProcessingTimeStarted = $this->applicationProcessingTimeStarted;
            $this->requestProcessingTimeStopped = microtime(true);
        } elseif ($event instanceof ConsoleTerminateEvent) {
            $this->requestProcessingTimeStopped = microtime(true);
        } elseif (str_ends_with($className, 'ApplicationStartup')) {
            // Framework-agnostic: match by class name suffix
            $this->applicationProcessingTimeStarted = microtime(true);
        } elseif (str_ends_with($className, 'ApplicationShutdown')) {
            $this->applicationProcessingTimeStopped = microtime(true);
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
