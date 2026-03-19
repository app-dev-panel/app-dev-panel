<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/timeline', name: 'test_timeline', methods: ['GET'])]
final readonly class TimelineAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Timeline step 1: start');
        usleep(10_000);
        $this->logger->info('Timeline step 2: processing');
        usleep(10_000);
        $this->logger->info('Timeline step 3: done');

        return new JsonResponse(['scenario' => 'timeline:basic', 'status' => 'ok']);
    }
}
