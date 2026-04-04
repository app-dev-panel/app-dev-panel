<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/queue', name: 'test_queue', methods: ['GET'])]
final readonly class QueueAction
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        // Dispatch messages via Symfony Messenger — the MessengerCollectorMiddleware
        // intercepts dispatch and feeds message data to QueueCollector.
        $this->messageBus->dispatch(new TestMessage(userId: 42, action: 'send_notification', payload: [
            'channel' => 'email',
            'subject' => 'Welcome to ADP',
        ]));

        $this->messageBus->dispatch(new TestMessage(userId: 1, action: 'process_payment', payload: [
            'orderId' => 'ORD-12345',
            'amount' => 99.99,
            'currency' => 'USD',
        ]));

        return new JsonResponse(['fixture' => 'queue:basic', 'status' => 'ok']);
    }
}
