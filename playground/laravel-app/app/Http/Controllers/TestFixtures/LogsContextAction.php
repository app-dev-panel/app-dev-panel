<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

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
