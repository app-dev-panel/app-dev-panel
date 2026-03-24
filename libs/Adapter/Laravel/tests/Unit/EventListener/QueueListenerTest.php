<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\QueueListener;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use PHPUnit\Framework\TestCase;

final class QueueListenerTest extends TestCase
{
    public function testRegistersThreeEventListeners(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new QueueListener($this->createCollector(...));
        $listener->register($dispatcher);

        $this->assertCount(3, $registeredListeners);
        $this->assertArrayHasKey(JobProcessing::class, $registeredListeners);
        $this->assertArrayHasKey(JobProcessed::class, $registeredListeners);
        $this->assertArrayHasKey(JobFailed::class, $registeredListeners);
    }

    public function testRecordsProcessedJob(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $job = $this->createMockJob('job-1', 'App\Jobs\SendEmail', 'default', 1);

        $listeners[JobProcessing::class](new JobProcessing('redis', $job));
        $listeners[JobProcessed::class](new JobProcessed('redis', $job));

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);
        $this->assertSame('App\Jobs\SendEmail', $collected['messages'][0]['messageClass']);
        $this->assertSame('redis', $collected['messages'][0]['bus']);
        $this->assertSame('default', $collected['messages'][0]['transport']);
        $this->assertTrue($collected['messages'][0]['handled']);
        $this->assertFalse($collected['messages'][0]['failed']);
    }

    public function testRecordsFailedJob(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $job = $this->createMockJob('job-2', 'App\Jobs\ProcessOrder', 'high', 3);
        $exception = new \RuntimeException('Connection refused');

        $listeners[JobProcessing::class](new JobProcessing('sqs', $job));
        $listeners[JobFailed::class](new JobFailed('sqs', $job, $exception));

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);
        $this->assertSame('App\Jobs\ProcessOrder', $collected['messages'][0]['messageClass']);
        $this->assertTrue($collected['messages'][0]['failed']);
        $this->assertStringContainsString('Connection refused', $collected['messages'][0]['message']['exception']);
    }

    public function testHandlesJobWithoutPriorProcessingEvent(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $job = $this->createMockJob('job-3', 'App\Jobs\Notify', 'default', 1);
        $listeners[JobProcessed::class](new JobProcessed('sync', $job));

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);
    }

    private function createMockJob(string $id, string $name, string $queue, int $attempts): Job
    {
        $job = $this->createMock(Job::class);
        $job->method('getJobId')->willReturn($id);
        $job->method('resolveName')->willReturn($name);
        $job->method('getQueue')->willReturn($queue);
        $job->method('attempts')->willReturn($attempts);
        return $job;
    }

    private function createCollector(): QueueCollector
    {
        $timeline = new TimelineCollector();
        $collector = new QueueCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    /**
     * @return array{QueueCollector, array<string, \Closure>}
     */
    private function registerListener(): array
    {
        $collector = $this->createCollector();
        $listeners = [];

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$listeners): void {
                $listeners[$event] = $callback;
            });

        $listener = new QueueListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $listeners];
    }
}
