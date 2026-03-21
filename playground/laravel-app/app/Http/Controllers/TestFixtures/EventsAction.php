<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use App\Events\TestFixtureEvent;
use Illuminate\Http\JsonResponse;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class EventsAction
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->eventDispatcher->dispatch(new TestFixtureEvent('events:basic'));

        return new JsonResponse(['fixture' => 'events:basic', 'status' => 'ok']);
    }
}
