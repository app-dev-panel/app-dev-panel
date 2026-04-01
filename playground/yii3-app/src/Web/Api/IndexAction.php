<?php

declare(strict_types=1);

namespace App\Web\Api;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class IndexAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responseFactory->createResponse([
            'message' => 'Welcome to the ADP Yii 3 Playground API!',
            'debug_panel' => '/debug/api/',
            'endpoints' => [
                'GET /api' => 'This page',
                'GET /api/users' => 'List users (demo)',
                'GET /api/error' => 'Trigger an exception (demo)',
            ],
        ]);
    }
}
