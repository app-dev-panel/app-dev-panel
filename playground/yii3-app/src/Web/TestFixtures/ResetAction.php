<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class ResetAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private StorageInterface $storage,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->storage->clear();

        return $this->responseFactory->createResponse(['fixture' => 'reset', 'status' => 'ok']);
    }
}
