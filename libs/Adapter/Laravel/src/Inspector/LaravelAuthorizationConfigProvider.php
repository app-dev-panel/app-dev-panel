<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Illuminate\Contracts\Foundation\Application;
use ReflectionClass;
use Throwable;

/**
 * Laravel implementation of {@see AuthorizationConfigProviderInterface}.
 *
 * Pulls configuration from:
 *  - `config('auth.guards')` / `config('auth.providers')` — authentication guards
 *  - The `Illuminate\Contracts\Auth\Access\Gate` instance — abilities and policies
 *    (read via reflection because Laravel does not expose them publicly)
 *
 * No role hierarchy is returned: Laravel has no built-in concept for it. If
 * `spatie/laravel-permission` is installed, a flat parent → children map is
 * derived from its `Role` eloquent model.
 *
 * Any missing component yields an empty section rather than an error so the
 * provider is safe to register unconditionally.
 */
final class LaravelAuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function getGuards(): array
    {
        $guardsConfig = $this->config('auth.guards');
        if (!is_array($guardsConfig)) {
            return [];
        }
        $providersConfig = $this->config('auth.providers');
        $providersConfig = is_array($providersConfig) ? $providersConfig : [];

        $guards = [];
        foreach ($guardsConfig as $name => $config) {
            if (!is_string($name) || $name === '' || !is_array($config)) {
                continue;
            }

            $providerName = '';
            if (isset($config['provider']) && is_string($config['provider'])) {
                $providerName = $config['provider'];
            }

            $providerClass = '';
            if (
                $providerName !== ''
                && isset($providersConfig[$providerName])
                && is_array($providersConfig[$providerName])
            ) {
                $providerDef = $providersConfig[$providerName];
                if (isset($providerDef['model']) && is_string($providerDef['model'])) {
                    $providerClass = $providerDef['model'];
                } elseif (isset($providerDef['driver']) && is_string($providerDef['driver'])) {
                    $providerClass = $providerDef['driver'];
                }
            }

            $guards[] = [
                'name' => $name,
                'provider' => $providerClass,
                'config' => $config,
            ];
        }

        return $guards;
    }

    public function getRoleHierarchy(): array
    {
        $roleClass = 'Spatie\\Permission\\Models\\Role';
        if (!class_exists($roleClass)) {
            return [];
        }

        try {
            /** @var iterable<object> $roles */
            $roles = $roleClass::with('permissions')->get();
        } catch (Throwable) {
            return [];
        }

        $hierarchy = [];
        foreach ($roles as $role) {
            if (!is_object($role) || !isset($role->name) || !is_string($role->name) || $role->name === '') {
                continue;
            }
            $children = [];
            $permissions = $role->permissions ?? null;
            if (is_iterable($permissions)) {
                foreach ($permissions as $permission) {
                    if (is_object($permission) && isset($permission->name) && is_string($permission->name)) {
                        $children[] = $permission->name;
                    }
                }
            }
            $hierarchy[$role->name] = $children;
        }

        ksort($hierarchy);

        return $hierarchy;
    }

    public function getVoters(): array
    {
        $gate = $this->resolveGate();
        if ($gate === null) {
            return [];
        }

        $voters = [];

        foreach ($this->readGateProperty($gate, 'abilities') as $name => $_callable) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $voters[] = [
                'name' => $name,
                'type' => 'ability',
                'priority' => null,
            ];
        }

        foreach ($this->readGateProperty($gate, 'policies') as $modelClass => $policyClass) {
            if (!is_string($modelClass) || $modelClass === '' || !is_string($policyClass) || $policyClass === '') {
                continue;
            }
            $voters[] = [
                'name' => $policyClass,
                'type' => 'policy',
                'priority' => null,
            ];
        }

        return $voters;
    }

    public function getSecurityConfig(): array
    {
        $authConfig = $this->config('auth');
        $config = [];

        if (is_array($authConfig)) {
            if (isset($authConfig['defaults']) && is_array($authConfig['defaults'])) {
                $config['defaults'] = $authConfig['defaults'];
            }
            if (isset($authConfig['providers']) && is_array($authConfig['providers'])) {
                $config['providers'] = $authConfig['providers'];
            }
            if (isset($authConfig['passwords']) && is_array($authConfig['passwords'])) {
                $config['passwords'] = $authConfig['passwords'];
            }
            if (isset($authConfig['password_timeout'])) {
                $config['password_timeout'] = $authConfig['password_timeout'];
            }
        }

        return $config;
    }

    private function config(string $key): mixed
    {
        try {
            if (!$this->app->bound('config')) {
                return null;
            }
            $repository = $this->app->make('config');
        } catch (Throwable) {
            return null;
        }
        if (!is_object($repository) || !method_exists($repository, 'get')) {
            return null;
        }
        try {
            return $repository->get($key);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveGate(): ?object
    {
        $gateInterface = 'Illuminate\\Contracts\\Auth\\Access\\Gate';
        try {
            if (!$this->app->bound($gateInterface)) {
                return null;
            }
            $gate = $this->app->make($gateInterface);
        } catch (Throwable) {
            return null;
        }
        return is_object($gate) ? $gate : null;
    }

    /**
     * @return array<string|int, mixed>
     */
    private function readGateProperty(object $gate, string $property): array
    {
        try {
            $reflection = new ReflectionClass($gate);
            do {
                if ($reflection->hasProperty($property)) {
                    $prop = $reflection->getProperty($property);
                    $value = $prop->getValue($gate);
                    return is_array($value) ? $value : [];
                }
                $parent = $reflection->getParentClass();
                if ($parent === false) {
                    return [];
                }
                $reflection = $parent;
            } while (true);
        } catch (Throwable) {
            return [];
        }

        return [];
    }
}
