<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\QueueCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

/**
 * Listens for Laravel queue events and feeds the QueueCollector.
 */
final class QueueListener
{
    /** @var \Closure(): QueueCollector */
    private \Closure $collectorFactory;

    /**
     * @var array<string, float>
     */
    private array $jobStartTimes = [];

    /**
     * @param \Closure(): QueueCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->jobStartTimes[$event->job->getJobId()] = microtime(true);
        });

        $events->listen(JobProcessed::class, function (JobProcessed $event): void {
            $jobId = $event->job->getJobId();
            $startTime = $this->jobStartTimes[$jobId] ?? microtime(true);
            $duration = (microtime(true) - $startTime) * 1000;
            unset($this->jobStartTimes[$jobId]);

            ($this->collectorFactory)()->logMessage(
                messageClass: $event->job->resolveName(),
                bus: $event->connectionName,
                transport: $event->job->getQueue(),
                dispatched: true,
                handled: true,
                failed: false,
                duration: $duration,
                message: [
                    'jobId' => $jobId,
                    'attempts' => $event->job->attempts(),
                ],
            );
        });

        $events->listen(JobFailed::class, function (JobFailed $event): void {
            $jobId = $event->job->getJobId();
            $startTime = $this->jobStartTimes[$jobId] ?? microtime(true);
            $duration = (microtime(true) - $startTime) * 1000;
            unset($this->jobStartTimes[$jobId]);

            ($this->collectorFactory)()->logMessage(
                messageClass: $event->job->resolveName(),
                bus: $event->connectionName,
                transport: $event->job->getQueue(),
                dispatched: true,
                handled: false,
                failed: true,
                duration: $duration,
                message: [
                    'jobId' => $jobId,
                    'exception' => $event->exception->getMessage(),
                ],
            );
        });
    }
}
