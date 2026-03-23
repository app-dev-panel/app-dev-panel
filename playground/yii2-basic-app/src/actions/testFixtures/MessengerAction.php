<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use yii\base\Action;

final class MessengerAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var QueueCollector|null $queueCollector */
        $queueCollector = $module->getCollector(QueueCollector::class);

        if ($queueCollector === null) {
            return ['fixture' => 'messenger:basic', 'status' => 'error', 'message' => 'QueueCollector not found'];
        }

        $queueCollector->logMessage(new MessageRecord(
            messageClass: 'App\\Message\\SendNotification',
            bus: 'default',
            transport: 'async',
            dispatched: true,
            handled: true,
            duration: 12.5,
            message: [
                'userId' => 42,
                'channel' => 'email',
                'subject' => 'Welcome to ADP',
            ],
        ));
        $queueCollector->logMessage(new MessageRecord(
            messageClass: 'App\\Message\\ProcessPayment',
            bus: 'default',
            transport: 'sync',
            dispatched: true,
            failed: true,
            duration: 45.0,
            message: [
                'orderId' => 'ORD-12345',
                'amount' => 99.99,
                'currency' => 'USD',
            ],
        ));

        return ['fixture' => 'messenger:basic', 'status' => 'ok'];
    }
}
