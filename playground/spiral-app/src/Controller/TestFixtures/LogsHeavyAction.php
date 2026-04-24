<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;

final class LogsHeavyAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        for ($i = 0; $i < 50; $i++) {
            $this->logger->info("Heavy log entry #{$i}", ['index' => $i, 'batch' => 'heavy']);
        }

        return ['fixture' => 'logs:heavy', 'status' => 'ok', 'logged' => 50];
    }
}
