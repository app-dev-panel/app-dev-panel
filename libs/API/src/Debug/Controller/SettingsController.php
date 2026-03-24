<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exposes panel configuration to the frontend.
 */
final class SettingsController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathMapperInterface $pathMapper = new NullPathMapper(),
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'pathMapping' => $this->pathMapper->getRules(),
        ]);
    }
}
