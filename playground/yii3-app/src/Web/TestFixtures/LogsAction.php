<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final readonly class LogsAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Test log: info level message');
        $this->logger->warning('Test log: warning level message');
        $this->logger->error('Test log: error level message');

        $this->logger->debug('Test log: debug with dump-like context', [
            'user' => ['id' => 42, 'name' => 'Alice', 'roles' => ['admin', 'editor']],
            'metadata' => ['session' => 'abc123', 'request_id' => 'req-789'],
        ]);
        VarDumper::dump(['fixture' => 'logs:basic', 'dump_example' => ['key' => 'value', 'nested' => [1, 2, 3]]]);

        $this->logger->notice('Test log: deprecated API usage detected');
        @trigger_error(
            'Method LegacyApi::doStuff() is deprecated since v2.0, use NewApi::doStuff() instead.',
            E_USER_DEPRECATED,
        );

        return $this->responseFactory->createResponse(['fixture' => 'logs:basic', 'status' => 'ok']);
    }
}
