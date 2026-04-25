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

        $this->logger->debug('Test log: debug with dump-like context', [
            'user' => ['id' => 42, 'name' => 'Alice', 'roles' => ['admin', 'editor']],
            'metadata' => ['session' => 'abc123', 'request_id' => 'req-789'],
        ]);
        dump(['fixture' => 'logs:basic', 'dump_example' => ['key' => 'value', 'nested' => [1, 2, 3]]]);

        $this->logger->notice('Test log: deprecated API usage detected');
        @trigger_error(
            'Method LegacyApi::doStuff() is deprecated since v2.0, use NewApi::doStuff() instead.',
            E_USER_DEPRECATED,
        );

        return new JsonResponse(['fixture' => 'logs:basic', 'status' => 'ok']);
    }
}
