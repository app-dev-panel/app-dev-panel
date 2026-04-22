<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Throwable;

/**
 * Symfony implementation of {@see AuthorizationConfigProviderInterface}.
 *
 * Reads Symfony's security configuration from the compiled container
 * (firewalls, role hierarchy) and the voter registry (tagged `security.voter`).
 *
 * Registered only when `symfony/security-bundle` is installed — otherwise the
 * {@see \AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider}
 * default remains in effect.
 */
final class SymfonyAuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    /**
     * @param array<string, mixed> $containerParameters All non-ADP container parameters collected at compile time.
     * @param iterable<object>     $voters              Services tagged `security.voter`.
     */
    public function __construct(
        private readonly array $containerParameters = [],
        private readonly iterable $voters = [],
    ) {}

    public function getGuards(): array
    {
        $firewalls = $this->containerParameters['security.firewalls'] ?? null;
        if (!is_array($firewalls)) {
            return [];
        }

        $guards = [];
        foreach ($firewalls as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $config = $this->collectFirewallConfig($name);
            $provider = '';
            if (isset($config['provider']) && is_string($config['provider'])) {
                $provider = $config['provider'];
            } elseif (isset($config['user_checker']) && is_string($config['user_checker'])) {
                $provider = $config['user_checker'];
            }

            $guards[] = [
                'name' => $name,
                'provider' => $provider,
                'config' => $config,
            ];
        }

        return $guards;
    }

    public function getRoleHierarchy(): array
    {
        $roles = $this->containerParameters['security.role_hierarchy.roles'] ?? null;
        if (!is_array($roles)) {
            return [];
        }

        $hierarchy = [];
        foreach ($roles as $role => $children) {
            if (!is_string($role) || $role === '') {
                continue;
            }
            $names = [];
            if (is_array($children)) {
                foreach ($children as $child) {
                    if (is_string($child) && $child !== '') {
                        $names[] = $child;
                    }
                }
            }
            $hierarchy[$role] = $names;
        }

        ksort($hierarchy);

        return $hierarchy;
    }

    public function getVoters(): array
    {
        $voters = [];
        foreach ($this->voters as $voter) {
            if (!is_object($voter)) {
                continue;
            }
            $voters[] = [
                'name' => $voter::class,
                'type' => $this->describeVoterType($voter),
                'priority' => null,
            ];
        }

        return $voters;
    }

    public function getSecurityConfig(): array
    {
        $config = [];

        $accessControl = $this->containerParameters['security.access_control'] ?? null;
        if (is_array($accessControl)) {
            $config['access_control'] = $accessControl;
        }

        $strategy = $this->containerParameters['security.access.decision_manager.strategy'] ?? null;
        if (is_string($strategy) && $strategy !== '') {
            $config['access_decision_strategy'] = $strategy;
        }

        $providers = [];
        foreach ($this->containerParameters as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'security.user.provider.concrete.')) {
                continue;
            }
            $providerName = substr($key, strlen('security.user.provider.concrete.'));
            if ($providerName !== '') {
                $providers[$providerName] = $value;
            }
        }
        if ($providers !== []) {
            ksort($providers);
            $config['providers'] = $providers;
        }

        $firewalls = $this->containerParameters['security.firewalls'] ?? null;
        if (is_array($firewalls)) {
            $config['firewalls'] = array_values(array_filter($firewalls, 'is_string'));
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectFirewallConfig(string $firewallName): array
    {
        $prefix = 'security.firewall.map.config.' . $firewallName;
        $legacyPrefix = 'security.firewall.' . $firewallName;
        $config = [];

        foreach ($this->containerParameters as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if ($key === $prefix || str_starts_with($key, $prefix . '.')) {
                $suffix = $key === $prefix ? '' : substr($key, strlen($prefix) + 1);
                $config[$suffix === '' ? 'config' : $suffix] = $value;
            } elseif ($key === $legacyPrefix || str_starts_with($key, $legacyPrefix . '.')) {
                $suffix = $key === $legacyPrefix ? '' : substr($key, strlen($legacyPrefix) + 1);
                if ($suffix !== '') {
                    $config[$suffix] = $value;
                }
            }
        }

        return $config;
    }

    private function describeVoterType(object $voter): string
    {
        try {
            $reflection = new \ReflectionClass($voter);
            $parent = $reflection->getParentClass();
            while ($parent !== false) {
                $name = $parent->getName();
                if ($name === 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\Voter') {
                    return 'voter';
                }
                if ($name === 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\RoleVoter') {
                    return 'role_voter';
                }
                $parent = $parent->getParentClass();
            }
            foreach ($reflection->getInterfaceNames() as $interface) {
                if ($interface === 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\VoterInterface') {
                    return 'voter';
                }
            }
        } catch (Throwable) {
            // Reflection failed — fall through to generic label.
        }

        return 'voter';
    }
}
