<?php

declare(strict_types=1);

namespace App\Web\Api;

use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class OpenApiAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $openapi = Generator::scan([dirname(__DIR__, 2)]);

        /** @var array<string, mixed> $spec */
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        return $this->responseFactory
            ->createResponse($spec)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}
