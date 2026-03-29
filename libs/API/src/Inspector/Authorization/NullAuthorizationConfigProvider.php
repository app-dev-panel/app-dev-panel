<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Authorization;

/**
 * Default no-op provider when no framework adapter supplies authorization config.
 */
final class NullAuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    public function getGuards(): array
    {
        return [];
    }

    public function getRoleHierarchy(): array
    {
        return [];
    }

    public function getVoters(): array
    {
        return [];
    }

    public function getSecurityConfig(): array
    {
        return [];
    }
}
