<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/logs-heavy', name: 'test_logs_heavy', methods: ['GET'])]
final readonly class LogsHeavyAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->logger->info(sprintf('Heavy log entry #%d', $i));
        }

        return new JsonResponse(['scenario' => 'logs:heavy', 'status' => 'ok', 'count' => 100]);
    }
}
