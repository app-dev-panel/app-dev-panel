<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class HttpClientAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('HTTP client scenario: would make external request');

        return $this->responseFactory->createResponse(['scenario' => 'http-client:basic', 'status' => 'ok']);
    }
}
