<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

final readonly class LogsHeavyAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        for ($i = 0; $i < 100; $i++) {
            $this->logger->info(sprintf('Heavy log entry #%d', $i));
        }

        return new JsonResponse(['fixture' => 'logs:heavy', 'status' => 'ok', 'count' => 100]);
    }
}
