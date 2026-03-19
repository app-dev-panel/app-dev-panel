<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/logs', name: 'test_logs', methods: ['GET'])]
final readonly class LogsAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Test log: info level message');
        $this->logger->warning('Test log: warning level message');
        $this->logger->error('Test log: error level message');

        return new JsonResponse(['fixture' => 'logs:basic', 'status' => 'ok']);
    }
}
