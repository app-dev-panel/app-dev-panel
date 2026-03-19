<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class TimelineAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Timeline step 1: start');
        usleep(10_000);
        $this->logger->info('Timeline step 2: processing');
        usleep(10_000);
        $this->logger->info('Timeline step 3: done');

        return $this->responseFactory->createResponse(['fixture' => 'timeline:basic', 'status' => 'ok']);
    }
}
