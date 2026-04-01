<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/logs-context', name: 'test_logs_context', methods: ['GET'])]
final readonly class LogsContextAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('User action', [
            'user_id' => 42,
            'action' => 'login',
            'ip' => '127.0.0.1',
        ]);

        return new JsonResponse(['fixture' => 'logs:context', 'status' => 'ok']);
    }
}
