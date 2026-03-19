<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/events', name: 'test_events', methods: ['GET'])]
final readonly class EventsAction
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->dispatcher->dispatch(new TestScenarioEvent('events:basic'));

        return new JsonResponse(['scenario' => 'events:basic', 'status' => 'ok']);
    }
}
