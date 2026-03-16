<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;

final class WebAppInfoCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    public const EVENT_APPLICATION_STARTUP = 'app.startup';
    public const EVENT_BEFORE_REQUEST = 'request.before';
    public const EVENT_AFTER_REQUEST = 'request.after';
    public const EVENT_AFTER_EMIT = 'response.emit';

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

    /**
     * Collect timing data based on event type.
     *
     * @param string $eventType One of the EVENT_* constants.
     */
    public function collectTiming(string $eventType): void
    {
        if (!$this->isActive()) {
            return;
        }

        match ($eventType) {
            self::EVENT_APPLICATION_STARTUP => $this->applicationProcessingTimeStarted = microtime(true),
            self::EVENT_BEFORE_REQUEST => $this->requestProcessingTimeStarted = microtime(true),
            self::EVENT_AFTER_REQUEST => $this->requestProcessingTimeStopped = microtime(true),
            self::EVENT_AFTER_EMIT => $this->applicationProcessingTimeStopped = microtime(true),
            default => null,
        };
        $this->timelineCollector->collect($this, crc32($eventType));
    }

    /**
     * Generic event-based collection for backward compatibility.
     */
    public function collect(object $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        // Map known event classes to timing types via class name suffix
        $className = $event::class;
        $eventType = match (true) {
            str_ends_with($className, 'ApplicationStartup') => self::EVENT_APPLICATION_STARTUP,
            str_ends_with($className, 'BeforeRequest') => self::EVENT_BEFORE_REQUEST,
            str_ends_with($className, 'AfterRequest') => self::EVENT_AFTER_REQUEST,
            str_ends_with($className, 'AfterEmit') => self::EVENT_AFTER_EMIT,
            default => null,
        };

        if ($eventType !== null) {
            $this->collectTiming($eventType);
        }
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
