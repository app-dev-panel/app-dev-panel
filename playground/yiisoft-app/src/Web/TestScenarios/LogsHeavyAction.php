<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class LogsHeavyAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->logger->info(sprintf('Heavy log entry #%d', $i));
        }

        return $this->responseFactory->createResponse(['scenario' => 'logs:heavy', 'status' => 'ok', 'count' => 100]);
    }
}
