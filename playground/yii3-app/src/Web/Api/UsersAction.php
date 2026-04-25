<?php

declare(strict_types=1);

namespace App\Web\Api;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class UsersAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {}

    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'List of users', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'users', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                ])),
            ])),
        ],
    )]
    public function __invoke(): ResponseInterface
    {
        $this->logger->info('Users API called');
        $this->logger->debug('Fetching users from database', ['limit' => 10]);

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        $this->logger->info('Users fetched', ['count' => count($users)]);

        return $this->responseFactory->createResponse(['users' => $users]);
    }
}
