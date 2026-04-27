<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;

final class LogsContextAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->logger->info('User action: login attempt', [
            'user_id' => 42,
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Spiral-Playground',
            'timestamp' => time(),
        ]);

        $this->logger->warning('Rate limit approaching', [
            'endpoint' => '/api/users',
            'current_rate' => 95,
            'limit' => 100,
        ]);

        return ['fixture' => 'logs:context', 'status' => 'ok'];
    }
}
