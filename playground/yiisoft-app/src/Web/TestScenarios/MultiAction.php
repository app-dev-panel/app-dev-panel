<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class MultiAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Multi scenario: log entry 1');
        $this->dispatcher->dispatch(new TestScenarioEvent('multi:step'));
        $this->logger->info('Multi scenario: log entry 2');

        return $this->responseFactory->createResponse(['scenario' => 'multi:logs-and-events', 'status' => 'ok']);
    }
}
