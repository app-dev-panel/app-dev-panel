<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Events\TestFixtureEvent;
use Illuminate\Http\JsonResponse;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final readonly class MultiAction
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Multi scenario: log entry 1');
        $this->eventDispatcher->dispatch(new TestFixtureEvent('multi:step'));
        $this->logger->info('Multi scenario: log entry 2');

        return new JsonResponse(['fixture' => 'multi:logs-and-events', 'status' => 'ok']);
    }
}
