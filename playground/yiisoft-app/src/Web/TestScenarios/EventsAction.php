<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class EventsAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->dispatcher->dispatch(new TestScenarioEvent('events:basic'));

        return $this->responseFactory->createResponse(['scenario' => 'events:basic', 'status' => 'ok']);
    }
}
