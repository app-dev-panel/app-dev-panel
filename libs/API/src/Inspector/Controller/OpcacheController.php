<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OpcacheController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        if (!\function_exists('opcache_get_status') || ($status = \opcache_get_status(true)) === false) {
            return $this->responseFactory->createJsonResponse([
                'message' => 'OPcache is not installed or configured',
            ], 422);
        }

        return $this->responseFactory->createJsonResponse([
            'status' => $status,
            'configuration' => \opcache_get_configuration(),
        ]);
    }
}
