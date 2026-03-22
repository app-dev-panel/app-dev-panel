<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\QueueCollector;
use Illuminate\Http\JsonResponse;

final readonly class MessengerAction
{
    public function __construct(
        private QueueCollector $queueCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->queueCollector->logMessage(
            messageClass: 'App\\Message\\SendNotification',
            bus: 'messenger.bus.default',
            transport: 'async',
            dispatched: true,
            handled: true,
            failed: false,
            duration: 12.5,
            message: [
                'userId' => 42,
                'channel' => 'email',
                'subject' => 'Welcome to ADP',
            ],
        );
        $this->queueCollector->logMessage(
            messageClass: 'App\\Message\\ProcessPayment',
            bus: 'messenger.bus.default',
            transport: 'sync',
            dispatched: true,
            handled: false,
            failed: true,
            duration: 45.0,
            message: [
                'orderId' => 'ORD-12345',
                'amount' => 99.99,
                'currency' => 'USD',
            ],
        );

        return new JsonResponse(['fixture' => 'messenger:basic', 'status' => 'ok']);
    }
}
