<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Authorization;

/**
 * Provides live authorization configuration from the target framework.
 *
 * Adapters implement this interface to expose their security configuration
 * (guards, role hierarchy, voters/policies) to the inspector.
 */
interface AuthorizationConfigProviderInterface
{
    /**
     * Returns configured guards/firewalls.
     *
     * @return array<int, array{name: string, provider: string, config: array<string, mixed>}>
     */
    public function getGuards(): array;

    /**
     * Returns role hierarchy mapping (role → child roles).
     *
     * @return array<string, string[]>
     */
    public function getRoleHierarchy(): array;

    /**
     * Returns registered voters, policies, or authorization checkers.
     *
     * @return array<int, array{name: string, type: string, priority: int|null}>
     */
    public function getVoters(): array;

    /**
     * Returns security-related configuration.
     *
     * @return array<string, mixed>
     */
    public function getSecurityConfig(): array;
}
