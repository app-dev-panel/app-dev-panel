<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class QueueAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private QueueCollector $queueCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->queueCollector->logMessage(new MessageRecord(
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
        $this->queueCollector->logMessage(new MessageRecord(
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

        return $this->responseFactory->createResponse(['fixture' => 'queue:basic', 'status' => 'ok']);
    }
}
