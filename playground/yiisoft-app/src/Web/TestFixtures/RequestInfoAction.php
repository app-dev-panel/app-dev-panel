<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class RequestInfoAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse(['fixture' => 'request:basic', 'status' => 'ok']);
    }
}
