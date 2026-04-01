<?php

declare(strict_types=1);

namespace App\Web\Api;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class UsersAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responseFactory->createResponse([
            'users' => [
                ['id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'admin'],
                ['id' => 2, 'name' => 'Bob Smith', 'email' => 'bob@example.com', 'role' => 'editor'],
                ['id' => 3, 'name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'role' => 'viewer'],
                ['id' => 4, 'name' => 'Diana Prince', 'email' => 'diana@example.com', 'role' => 'editor'],
                ['id' => 5, 'name' => 'Eve Wilson', 'email' => 'eve@example.com', 'role' => 'viewer'],
            ],
        ]);
    }
}
