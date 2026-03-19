<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/multi', name: 'test_multi', methods: ['GET'])]
final readonly class MultiAction
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Multi scenario: log entry 1');
        $this->dispatcher->dispatch(new TestScenarioEvent('multi:step'));
        $this->logger->info('Multi scenario: log entry 2');

        return new JsonResponse(['scenario' => 'multi:logs-and-events', 'status' => 'ok']);
    }
}
