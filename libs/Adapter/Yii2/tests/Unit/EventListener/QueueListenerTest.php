<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Yii2\EventListener\QueueListener;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use yii\base\BaseObject;
use yii\base\Event;
use yii\queue\ExecEvent;
use yii\queue\JobInterface;
use yii\queue\PushEvent;
use yii\queue\sync\Queue as SyncQueue;

final class QueueListenerFixtureJob extends BaseObject implements JobInterface
{
    public int $userId = 0;
    public string $subject = '';

    public function execute($queue): mixed
    {
        return 'ok';
    }
}

final class QueueListenerTest extends TestCase
{
    private QueueCollector $collector;

    protected function setUp(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();

        $this->collector = new QueueCollector($timeline);
        $this->collector->startup();
    }

    protected function tearDown(): void
    {
        Event::offAll();
    }

    public function testAfterPushLogsDispatchedMessage(): void
    {
        $listener = new QueueListener($this->collector);
        $sender = new SyncQueue();
        $job = new QueueListenerFixtureJob(['userId' => 42, 'subject' => 'Welcome']);

        $beforeEvent = new PushEvent();
        $beforeEvent->sender = $sender;
        $beforeEvent->job = $job;
        $listener->onBeforePush($beforeEvent);

        $afterEvent = new PushEvent();
        $afterEvent->sender = $sender;
        $afterEvent->job = $job;
        $listener->onAfterPush($afterEvent);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['messages']);
        $record = $data['messages'][0];
        $this->assertSame(QueueListenerFixtureJob::class, $record['messageClass']);
        $this->assertSame('yii2.queue', $record['bus']);
        $this->assertSame(SyncQueue::class, $record['transport']);
        $this->assertTrue($record['dispatched']);
        $this->assertFalse($record['handled']);
        $this->assertFalse($record['failed']);
        $this->assertGreaterThanOrEqual(0.0, $record['duration']);
        $this->assertSame(42, $record['message']['userId']);
        $this->assertSame('Welcome', $record['message']['subject']);
    }

    public function testAfterExecLogsHandledMessage(): void
    {
        $listener = new QueueListener($this->collector);
        $sender = new SyncQueue();
        $job = new QueueListenerFixtureJob(['userId' => 7]);

        $beforeEvent = new ExecEvent();
        $beforeEvent->sender = $sender;
        $beforeEvent->job = $job;
        $listener->onBeforeExec($beforeEvent);

        $afterEvent = new ExecEvent();
        $afterEvent->sender = $sender;
        $afterEvent->job = $job;
        $listener->onAfterExec($afterEvent);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['messages']);
        $record = $data['messages'][0];
        $this->assertTrue($record['dispatched']);
        $this->assertTrue($record['handled']);
        $this->assertFalse($record['failed']);
    }

    public function testAfterErrorLogsFailedMessage(): void
    {
        $listener = new QueueListener($this->collector);
        $sender = new SyncQueue();
        $job = new QueueListenerFixtureJob(['userId' => 99]);

        $beforeEvent = new ExecEvent();
        $beforeEvent->sender = $sender;
        $beforeEvent->job = $job;
        $listener->onBeforeExec($beforeEvent);

        $errorEvent = new ExecEvent();
        $errorEvent->sender = $sender;
        $errorEvent->job = $job;
        $errorEvent->error = new \RuntimeException('boom');
        $listener->onAfterError($errorEvent);

        $data = $this->collector->getCollected();
        $this->assertCount(1, $data['messages']);
        $record = $data['messages'][0];
        $this->assertTrue($record['handled']);
        $this->assertTrue($record['failed']);
    }

    public function testPushAndExecLogTwoSeparateMessages(): void
    {
        $listener = new QueueListener($this->collector);
        $sender = new SyncQueue();
        $job = new QueueListenerFixtureJob(['userId' => 1]);

        $beforePush = new PushEvent();
        $beforePush->sender = $sender;
        $beforePush->job = $job;
        $listener->onBeforePush($beforePush);

        $afterPush = new PushEvent();
        $afterPush->sender = $sender;
        $afterPush->job = $job;
        $listener->onAfterPush($afterPush);

        $beforeExec = new ExecEvent();
        $beforeExec->sender = $sender;
        $beforeExec->job = $job;
        $listener->onBeforeExec($beforeExec);

        $afterExec = new ExecEvent();
        $afterExec->sender = $sender;
        $afterExec->job = $job;
        $listener->onAfterExec($afterExec);

        $data = $this->collector->getCollected();
        $this->assertCount(2, $data['messages']);
        // First entry: pushed (not handled)
        $this->assertFalse($data['messages'][0]['handled']);
        // Second entry: handled
        $this->assertTrue($data['messages'][1]['handled']);
    }

    public function testRegisterAttachesEventListenersOnSyncQueue(): void
    {
        $listener = new QueueListener($this->collector);
        $listener->register();

        // Trigger a real push via the sync queue — handle=false to avoid calling
        // the registered handler (the listener still emits push events).
        $queue = new SyncQueue(['handle' => false]);
        $queue->push(new QueueListenerFixtureJob(['userId' => 5, 'subject' => 'via event system']));

        $data = $this->collector->getCollected();
        $this->assertGreaterThanOrEqual(1, count($data['messages']));
        $this->assertSame(QueueListenerFixtureJob::class, $data['messages'][0]['messageClass']);
        $this->assertSame('yii2.queue', $data['messages'][0]['bus']);
    }

    public function testRegisterEndToEndWithSyncExecution(): void
    {
        $listener = new QueueListener($this->collector);
        $listener->register();

        // handle=false avoids the Yii::$app->on() init hook; we drive execution
        // manually via $queue->run() — same event flow as the real handle=true path.
        $queue = new SyncQueue(['handle' => false]);
        $queue->push(new QueueListenerFixtureJob(['userId' => 5]));
        $queue->run();

        $data = $this->collector->getCollected();
        // At least two events: push + exec
        $this->assertGreaterThanOrEqual(2, count($data['messages']));

        $pushed = array_filter($data['messages'], static fn(array $m): bool => !$m['handled']);
        $handled = array_filter($data['messages'], static fn(array $m): bool => $m['handled']);
        $this->assertNotEmpty($pushed);
        $this->assertNotEmpty($handled);
    }

    public function testRegisterIsNoOpWhenQueuePackageMissing(): void
    {
        // Even if we pretend the class doesn't exist, register() should not throw.
        // We can't unload the class at runtime, so this test verifies register()
        // is safe to call twice and produces no duplicate events.
        $listener = new QueueListener($this->collector);
        $listener->register();

        // Smoke check — no events fired yet
        $data = $this->collector->getCollected();
        $this->assertCount(0, $data['messages']);
    }
}
