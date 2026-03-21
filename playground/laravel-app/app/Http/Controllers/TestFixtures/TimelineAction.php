<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

final readonly class TimelineAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->logger->info('Timeline entry 1');
        usleep(10_000);
        $this->logger->info('Timeline entry 2');
        usleep(10_000);
        $this->logger->info('Timeline entry 3');

        return new JsonResponse(['fixture' => 'timeline:basic', 'status' => 'ok']);
    }
}
