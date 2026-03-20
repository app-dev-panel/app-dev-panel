<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\QueueCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/messenger', name: 'test_messenger', methods: ['GET'])]
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
        );
        $this->queueCollector->logMessage(
            messageClass: 'App\\Message\\ProcessPayment',
            bus: 'messenger.bus.default',
            transport: 'sync',
            dispatched: true,
            handled: false,
            failed: true,
            duration: 45.0,
        );

        return new JsonResponse(['fixture' => 'messenger:basic', 'status' => 'ok']);
    }
}
