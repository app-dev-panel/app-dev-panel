<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Events\TestFixtureEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

final readonly class MultiAction
{
    public function __construct(
        private LoggerInterface $logger,
        private Dispatcher $eventDispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Multi scenario: log entry 1');
        $this->eventDispatcher->dispatch(new TestFixtureEvent('multi:step'));
        $this->logger->info('Multi scenario: log entry 2');

        return new JsonResponse(['fixture' => 'multi:logs-and-events', 'status' => 'ok']);
    }
}
