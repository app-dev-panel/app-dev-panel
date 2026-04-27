<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Log\LoggerInterface;

final class LogsAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->logger->info('Test log: info level message');
        $this->logger->warning('Test log: warning level message');
        $this->logger->error('Test log: error level message');

        $this->logger->debug('Test log: debug with dump-like context', [
            'user' => ['id' => 42, 'name' => 'Alice', 'roles' => ['admin', 'editor']],
            'metadata' => ['session' => 'abc123', 'request_id' => 'req-789'],
        ]);
        dump(['fixture' => 'logs:basic', 'dump_example' => ['key' => 'value', 'nested' => [1, 2, 3]]]);

        $this->logger->notice('Test log: deprecated API usage detected');

        return ['fixture' => 'logs:basic', 'status' => 'ok'];
    }
}
