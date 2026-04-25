<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * Dummy job: logs its payload through the Yii logger when executed.
 */
final class TestFixtureJob extends BaseObject implements JobInterface
{
    public int $userId = 0;
    public string $channel = 'email';
    public string $subject = '';

    public function execute($queue): void
    {
        \Yii::info(
            sprintf(
                'TestFixtureJob executed: user=%d, channel=%s, subject=%s',
                $this->userId,
                $this->channel,
                $this->subject,
            ),
            __METHOD__,
        );
    }
}

/**
 * Pushes real jobs to `Yii::$app->queue` so the adapter's queue event listeners
 * feed `QueueCollector`. With the sync driver (`'handle' => true`), each push
 * also triggers execution — producing BEFORE_EXEC/AFTER_EXEC events.
 *
 * No direct collector calls — event listeners do the recording.
 */
final class QueueAction extends Action
{
    public function run(): array
    {
        if (!class_exists(Queue::class)) {
            return ['fixture' => 'queue:basic', 'status' => 'error', 'message' => 'yii2-queue not installed'];
        }

        $queue = \Yii::$app->queue ?? null;

        if (!$queue instanceof Queue) {
            return ['fixture' => 'queue:basic', 'status' => 'error', 'message' => 'queue component not configured'];
        }

        $queue->push(new TestFixtureJob([
            'userId' => 42,
            'channel' => 'email',
            'subject' => 'Welcome to ADP',
        ]));

        $queue->push(new TestFixtureJob([
            'userId' => 7,
            'channel' => 'sms',
            'subject' => 'Your verification code',
        ]));

        $queue->push(new TestFixtureJob([
            'userId' => 128,
            'channel' => 'push',
            'subject' => 'Daily summary',
        ]));

        return ['fixture' => 'queue:basic', 'status' => 'ok'];
    }
}
