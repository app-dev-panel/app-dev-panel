<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

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

        return $this->responseFactory->createResponse(['fixture' => 'logs:basic', 'status' => 'ok']);
    }
}
