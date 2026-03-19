<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/events', name: 'test_events', methods: ['GET'])]
final readonly class EventsAction
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->dispatcher->dispatch(new TestFixtureEvent('events:basic'));

        return new JsonResponse(['fixture' => 'events:basic', 'status' => 'ok']);
    }
}
