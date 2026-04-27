<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;

final class TimelineAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->logger->info('Timeline step 1: start');
        usleep(10_000);
        $this->logger->info('Timeline step 2: processing');
        usleep(10_000);
        $this->logger->info('Timeline step 3: done');

        return ['fixture' => 'timeline:basic', 'status' => 'ok'];
    }
}
