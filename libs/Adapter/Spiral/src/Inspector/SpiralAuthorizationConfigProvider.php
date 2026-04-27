<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Spiral implementation of {@see AuthorizationConfigProviderInterface}.
 *
 * Reads `spiral/auth` services from the container — `Spiral\Auth\TokenStorageInterface`
 * and `Spiral\Auth\ActorProviderInterface`. When `spiral/auth` is not installed (the
 * playground does not ship it) every method returns the empty arrays of
 * {@see \AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider},
 * which makes this provider safe to bind unconditionally.
 */
final class SpiralAuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function getGuards(): array
    {
        $tokenStorageInterface = 'Spiral\\Auth\\TokenStorageInterface';
        $actorProviderInterface = 'Spiral\\Auth\\ActorProviderInterface';

        if (!interface_exists($tokenStorageInterface)) {
            return [];
        }
        if (!$this->container->has($tokenStorageInterface)) {
            return [];
        }

        try {
            $tokenStorage = $this->container->get($tokenStorageInterface);
        } catch (Throwable) {
            return [];
        }
        if (!is_object($tokenStorage)) {
            return [];
        }

        $config = [
            'tokenStorage' => $tokenStorage::class,
        ];

        if (interface_exists($actorProviderInterface) && $this->container->has($actorProviderInterface)) {
            try {
                $actorProvider = $this->container->get($actorProviderInterface);
                if (is_object($actorProvider)) {
                    $config['actorProvider'] = $actorProvider::class;
                }
            } catch (Throwable) {
                // Optional dependency — ignore.
            }
        }

        return [
            [
                'name' => 'spiral',
                'provider' => $config['actorProvider'] ?? '',
                'config' => $config,
            ],
        ];
    }

    public function getRoleHierarchy(): array
    {
        // Spiral has no native RBAC hierarchy — `spiral/security` exposes a flat permission
        // model rather than role inheritance. Returning empty matches the Null provider.
        return [];
    }

    public function getVoters(): array
    {
        // Spiral's permissions/roles are policy-based via `Spiral\Security\PermissionsInterface`
        // and don't expose a "voter" concept comparable to Symfony's Security voters.
        return [];
    }

    public function getSecurityConfig(): array
    {
        $tokenStorageInterface = 'Spiral\\Auth\\TokenStorageInterface';
        if (!interface_exists($tokenStorageInterface) || !$this->container->has($tokenStorageInterface)) {
            return [];
        }

        try {
            $tokenStorage = $this->container->get($tokenStorageInterface);
        } catch (Throwable) {
            return [];
        }

        if (!is_object($tokenStorage)) {
            return [];
        }

        return [
            'tokenStorage' => $tokenStorage::class,
        ];
    }
}
