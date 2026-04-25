<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Throwable;
use yii\base\Application;
use yii\rbac\Item;
use yii\rbac\ManagerInterface;

/**
 * Yii 2 implementation of {@see AuthorizationConfigProviderInterface}.
 *
 * Data sources:
 *  - `Yii::$app->user` — the web user component (guard). Reads its `identityClass`,
 *    login settings, and runtime state (`isGuest`, `id`).
 *  - `Yii::$app->authManager` — optional `yii\rbac\ManagerInterface`. When present,
 *    roles + their child roles are exposed as the hierarchy, and all RBAC items
 *    (roles, permissions, rules) are listed as voters.
 *
 * Missing components produce empty sections rather than errors, so the class
 * can be registered unconditionally — even in apps that only use session auth
 * without RBAC.
 */
final class Yii2AuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    public function __construct(
        private readonly ?Application $app,
    ) {}

    public function getGuards(): array
    {
        if ($this->app === null || !$this->app->has('user')) {
            return [];
        }

        $user = $this->tryGetComponent('user');
        if ($user === null) {
            return [];
        }

        $provider = '';
        if (property_exists($user, 'identityClass') && is_string($user->identityClass)) {
            $provider = $user->identityClass;
        }

        $config = [];
        foreach (['loginUrl', 'enableSession', 'authTimeout', 'absoluteAuthTimeout', 'enableAutoLogin'] as $property) {
            if (!property_exists($user, $property)) {
                continue;
            }
            try {
                $value = $user->{$property};
            } catch (Throwable) {
                continue;
            }
            if ($value === null || is_scalar($value) || is_array($value)) {
                $config[$property] = $value;
            }
        }

        return [[
            'name' => 'user',
            'provider' => $provider,
            'config' => $config,
        ]];
    }

    public function getRoleHierarchy(): array
    {
        $manager = $this->authManager();
        if ($manager === null) {
            return [];
        }

        try {
            /** @var array<string, Item> $roles */
            $roles = $manager->getRoles();
        } catch (Throwable) {
            return [];
        }

        $hierarchy = [];
        foreach ($roles as $name => $role) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $children = [];
            try {
                /** @var array<string, Item> $rawChildren */
                $rawChildren = $manager->getChildren($name);
            } catch (Throwable) {
                $rawChildren = [];
            }
            foreach ($rawChildren as $childName => $_child) {
                if (is_string($childName) && $childName !== '') {
                    $children[] = $childName;
                }
            }
            $hierarchy[$name] = $children;
        }

        ksort($hierarchy);

        return $hierarchy;
    }

    public function getVoters(): array
    {
        $manager = $this->authManager();
        if ($manager === null) {
            return [];
        }

        $voters = [];

        try {
            /** @var array<string, Item> $roles */
            $roles = $manager->getRoles();
        } catch (Throwable) {
            $roles = [];
        }
        foreach ($roles as $name => $_role) {
            if (is_string($name) && $name !== '') {
                $voters[] = ['name' => $name, 'type' => 'role', 'priority' => null];
            }
        }

        try {
            /** @var array<string, Item> $permissions */
            $permissions = $manager->getPermissions();
        } catch (Throwable) {
            $permissions = [];
        }
        foreach ($permissions as $name => $_permission) {
            if (is_string($name) && $name !== '') {
                $voters[] = ['name' => $name, 'type' => 'permission', 'priority' => null];
            }
        }

        try {
            /** @var array<string, object> $rules */
            $rules = $manager->getRules();
        } catch (Throwable) {
            $rules = [];
        }
        foreach ($rules as $name => $rule) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $voters[] = [
                'name' => is_object($rule) ? $rule::class : $name,
                'type' => 'rule',
                'priority' => null,
            ];
        }

        return $voters;
    }

    public function getSecurityConfig(): array
    {
        if ($this->app === null) {
            return [];
        }

        $config = [];

        $user = $this->tryGetComponent('user');
        if ($user !== null) {
            $userInfo = [];
            if (property_exists($user, 'identityClass') && is_string($user->identityClass)) {
                $userInfo['identityClass'] = $user->identityClass;
            }
            foreach (['isGuest', 'id'] as $property) {
                if (!property_exists($user, $property)) {
                    continue;
                }
                try {
                    $value = $user->{$property};
                } catch (Throwable) {
                    continue;
                }
                if ($value === null || is_scalar($value)) {
                    $userInfo[$property] = $value;
                }
            }
            if ($userInfo !== []) {
                $config['user'] = $userInfo;
            }
        }

        $manager = $this->authManager();
        if ($manager !== null) {
            $managerInfo = ['class' => $manager::class];
            if (property_exists($manager, 'defaultRoles') && is_array($manager->defaultRoles)) {
                $managerInfo['defaultRoles'] = array_values(array_filter($manager->defaultRoles, 'is_string'));
            }
            $config['authManager'] = $managerInfo;
        }

        return $config;
    }

    private function authManager(): ?ManagerInterface
    {
        if ($this->app === null || !$this->app->has('authManager')) {
            return null;
        }

        $manager = $this->tryGetComponent('authManager');

        return $manager instanceof ManagerInterface ? $manager : null;
    }

    private function tryGetComponent(string $id): ?object
    {
        if ($this->app === null) {
            return null;
        }
        try {
            $component = $this->app->get($id, false);
        } catch (Throwable) {
            return null;
        }

        return is_object($component) ? $component : null;
    }
}
