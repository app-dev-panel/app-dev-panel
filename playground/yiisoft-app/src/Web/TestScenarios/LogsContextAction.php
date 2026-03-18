<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class LogsContextAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('User action', [
            'user_id' => 42,
            'action' => 'login',
            'ip' => '127.0.0.1',
        ]);

        return $this->responseFactory->createResponse(['scenario' => 'logs:context', 'status' => 'ok']);
    }
}
