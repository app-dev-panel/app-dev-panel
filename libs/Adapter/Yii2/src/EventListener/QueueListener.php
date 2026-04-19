<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\EventListener;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use yii\base\Event;

/**
 * Feeds {@see QueueCollector} from `yii\queue\Queue` events.
 *
 * Tracks push/exec start times keyed by the job object identity so
 * nested push→exec flows do not clobber each other. No-op at registration
 * time if `yiisoft/yii2-queue` is not installed.
 */
final class QueueListener
{
    private const BUS_NAME = 'yii2.queue';

    /** @var array<int, float> */
    private array $pushTimers = [];

    /** @var array<int, float> */
    private array $execTimers = [];

    public function __construct(
        private readonly QueueCollector $collector,
    ) {}

    public function register(): void
    {
        if (!class_exists(\yii\queue\Queue::class)) {
            return;
        }

        Event::on(\yii\queue\Queue::class, \yii\queue\Queue::EVENT_BEFORE_PUSH, [$this, 'onBeforePush']);
        Event::on(\yii\queue\Queue::class, \yii\queue\Queue::EVENT_AFTER_PUSH, [$this, 'onAfterPush']);
        Event::on(\yii\queue\Queue::class, \yii\queue\Queue::EVENT_BEFORE_EXEC, [$this, 'onBeforeExec']);
        Event::on(\yii\queue\Queue::class, \yii\queue\Queue::EVENT_AFTER_EXEC, [$this, 'onAfterExec']);
        Event::on(\yii\queue\Queue::class, \yii\queue\Queue::EVENT_AFTER_ERROR, [$this, 'onAfterError']);
    }

    public function onBeforePush(\yii\queue\PushEvent $event): void
    {
        if (is_object($event->job)) {
            $this->pushTimers[spl_object_id($event->job)] = microtime(true);
        }
    }

    public function onAfterPush(\yii\queue\PushEvent $event): void
    {
        $duration = $this->consumeTimer($this->pushTimers, $event->job);

        $this->collector->logMessage(new MessageRecord(
            messageClass: self::jobClassName($event->job),
            bus: self::BUS_NAME,
            transport: self::queueTransport($event->sender),
            dispatched: true,
            handled: false,
            failed: false,
            duration: $duration,
            message: self::jobPayload($event->job),
        ));
    }

    public function onBeforeExec(\yii\queue\ExecEvent $event): void
    {
        if (is_object($event->job)) {
            $this->execTimers[spl_object_id($event->job)] = microtime(true);
        }
    }

    public function onAfterExec(\yii\queue\ExecEvent $event): void
    {
        $duration = $this->consumeTimer($this->execTimers, $event->job);

        $this->collector->logMessage(new MessageRecord(
            messageClass: self::jobClassName($event->job),
            bus: self::BUS_NAME,
            transport: self::queueTransport($event->sender),
            dispatched: true,
            handled: true,
            failed: false,
            duration: $duration,
            message: self::jobPayload($event->job),
        ));
    }

    public function onAfterError(\yii\queue\ExecEvent $event): void
    {
        $duration = $this->consumeTimer($this->execTimers, $event->job);

        $this->collector->logMessage(new MessageRecord(
            messageClass: self::jobClassName($event->job),
            bus: self::BUS_NAME,
            transport: self::queueTransport($event->sender),
            dispatched: true,
            handled: true,
            failed: true,
            duration: $duration,
            message: self::jobPayload($event->job),
        ));
    }

    /**
     * @param array<int, float> $timers
     */
    private function consumeTimer(array &$timers, mixed $job): float
    {
        if (!is_object($job)) {
            return 0.0;
        }

        $id = spl_object_id($job);
        if (!isset($timers[$id])) {
            return 0.0;
        }

        $elapsed = microtime(true) - $timers[$id];
        unset($timers[$id]);

        return $elapsed;
    }

    private static function jobClassName(mixed $job): string
    {
        if (is_object($job)) {
            return $job::class;
        }
        if (is_string($job)) {
            return $job;
        }
        return 'mixed';
    }

    private static function jobPayload(mixed $job): mixed
    {
        if (!is_object($job)) {
            return $job;
        }

        $payload = [];
        foreach (get_object_vars($job) as $name => $value) {
            $payload[$name] = $value;
        }
        return $payload;
    }

    private static function queueTransport(mixed $sender): string
    {
        if (is_object($sender)) {
            return $sender::class;
        }
        return 'unknown';
    }
}
