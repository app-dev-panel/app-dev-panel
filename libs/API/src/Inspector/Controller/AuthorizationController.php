<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Kernel\Inspector\Primitives;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Inspector controller for live authorization configuration inspection.
 *
 * Exposes guards, role hierarchy, voters/policies, and security config
 * from the running application via {@see AuthorizationConfigProviderInterface}.
 */
final class AuthorizationController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly AuthorizationConfigProviderInterface $configProvider,
    ) {}

    /**
     * GET /inspect/api/authorization — full authorization configuration.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'guards' => $this->configProvider->getGuards(),
            'roleHierarchy' => $this->configProvider->getRoleHierarchy(),
            'voters' => $this->configProvider->getVoters(),
            'config' => $this->configProvider->getSecurityConfig(),
        ];

        $response = Primitives::dump($data, 5);

        return $this->responseFactory->createJsonResponse($response);
    }
}
