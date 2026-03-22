<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

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
