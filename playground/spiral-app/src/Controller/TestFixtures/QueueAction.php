<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;

final class QueueAction
{
    public function __construct(
        private readonly QueueCollector $queue,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->queue->logMessage(new MessageRecord(
            messageClass: 'App\\Message\\SendEmailMessage',
            bus: 'default',
            transport: 'sync',
            dispatched: true,
            handled: true,
            duration: 0.001,
        ));
        $this->queue->logMessage(new MessageRecord(
            messageClass: 'App\\Message\\GenerateReportMessage',
            bus: 'default',
            transport: 'async',
            dispatched: true,
            handled: true,
            duration: 0.005,
        ));
        $this->queue->logMessage(new MessageRecord(
            messageClass: 'App\\Message\\FailingMessage',
            bus: 'default',
            transport: 'async',
            dispatched: true,
            handled: false,
            failed: true,
            duration: 0.002,
        ));

        return ['fixture' => 'queue:basic', 'status' => 'ok'];
    }
}
