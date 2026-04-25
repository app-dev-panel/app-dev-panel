<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Inspector;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Yii 3 implementation of {@see AuthorizationConfigProviderInterface}.
 *
 * Exposes data from Yii's authorization ecosystem when the corresponding
 * packages are installed (all optional):
 *  - `yiisoft/rbac` — role/permission hierarchy
 *  - `yiisoft/user` — current identity / guest role
 *  - `yiisoft/auth` — authentication methods (as "guards")
 *  - `yiisoft/access` — access checkers (as "voters")
 *
 * Missing packages produce empty sections rather than errors, so the class
 * can safely be registered in any Yii 3 application regardless of which
 * auth-related packages are present.
 */
final class Yii3AuthorizationConfigProvider implements AuthorizationConfigProviderInterface
{
    /**
     * @param array<string, mixed> $params `app-dev-panel/yii3` params subtree.
     *                                     Typically provided via `params.php`.
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $params = [],
    ) {}

    public function getGuards(): array
    {
        $guards = [];

        foreach ($this->discoverAuthenticationMethods() as $name => $instance) {
            $guards[] = [
                'name' => $name,
                'provider' => $instance::class,
                'config' => $this->describeAuthenticationMethod($instance),
            ];
        }

        return $guards;
    }

    public function getRoleHierarchy(): array
    {
        $itemsStorage = $this->tryGet('Yiisoft\\Rbac\\ItemsStorageInterface');
        if ($itemsStorage === null || !method_exists($itemsStorage, 'getAll')) {
            return [];
        }

        $hierarchy = [];

        try {
            /** @var iterable<object> $items */
            $items = $itemsStorage->getAll();
        } catch (Throwable) {
            return [];
        }

        foreach ($items as $item) {
            if (!method_exists($item, 'getName')) {
                continue;
            }
            $name = (string) $item->getName();
            if ($name === '') {
                continue;
            }
            $hierarchy[$name] = $this->collectChildNames($itemsStorage, $name);
        }

        ksort($hierarchy);

        return $hierarchy;
    }

    public function getVoters(): array
    {
        $voters = [];

        $accessChecker = $this->tryGet('Yiisoft\\Access\\AccessCheckerInterface');
        if ($accessChecker !== null) {
            $voters[] = [
                'name' => $accessChecker::class,
                'type' => 'access_checker',
                'priority' => null,
            ];
        }

        foreach ($this->discoverRbacRules() as $rule) {
            $voters[] = $rule;
        }

        return $voters;
    }

    public function getSecurityConfig(): array
    {
        $config = [];

        $userParams = $this->params['user'] ?? null;
        if (is_array($userParams)) {
            $config['user'] = $userParams;
        }

        $rbacParams = $this->params['rbac'] ?? null;
        if (is_array($rbacParams)) {
            $config['rbac'] = $rbacParams;
        }

        $authParams = $this->params['auth'] ?? null;
        if (is_array($authParams)) {
            $config['auth'] = $authParams;
        }

        $currentUser = $this->tryGet('Yiisoft\\User\\CurrentUser');
        if ($currentUser !== null) {
            $config['currentUser'] = $this->describeCurrentUser($currentUser);
        }

        return $config;
    }

    /**
     * @return array<string, object>
     */
    private function discoverAuthenticationMethods(): array
    {
        $candidates = [
            'Yiisoft\\Auth\\Method\\HttpBasic',
            'Yiisoft\\Auth\\Method\\HttpBearer',
            'Yiisoft\\Auth\\Method\\HttpHeader',
            'Yiisoft\\Auth\\Method\\QueryParam',
            'Yiisoft\\Auth\\Method\\Composite',
            'Yiisoft\\Auth\\AuthenticationMethodInterface',
        ];

        $methods = [];
        foreach ($candidates as $class) {
            $instance = $this->tryGet($class);
            if ($instance === null) {
                continue;
            }
            // De-duplicate: container may resolve the interface to one of the concrete classes.
            $methods[$instance::class] = $instance;
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeAuthenticationMethod(object $instance): array
    {
        $config = [];
        foreach (['realm', 'headerName', 'tokenHeader', 'queryParameterName', 'pattern'] as $property) {
            if (!property_exists($instance, $property)) {
                continue;
            }
            try {
                $reflection = new \ReflectionProperty($instance, $property);
                $value = $reflection->getValue($instance);
                if ($value === null || is_scalar($value)) {
                    $config[$property] = $value;
                }
            } catch (Throwable $_) {
                continue;
            }
        }

        return $config;
    }

    /**
     * @return list<string>
     */
    private function collectChildNames(object $itemsStorage, string $parentName): array
    {
        // `getDirectChildren()` is the `yiisoft/rbac` v2 API; `getChildren()` is the v1 API.
        $method = match (true) {
            method_exists($itemsStorage, 'getDirectChildren') => 'getDirectChildren',
            method_exists($itemsStorage, 'getChildren') => 'getChildren',
            default => null,
        };
        if ($method === null) {
            return [];
        }

        try {
            /** @var iterable<object>|array<string, object> $children */
            $children = $itemsStorage->{$method}($parentName);
        } catch (Throwable) {
            return [];
        }

        $names = [];
        foreach ($children as $key => $child) {
            if (is_object($child) && method_exists($child, 'getName')) {
                $name = (string) $child->getName();
            } elseif (is_string($key) && $key !== '') {
                $name = $key;
            } else {
                continue;
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return list<array{name: string, type: string, priority: int|null}>
     */
    private function discoverRbacRules(): array
    {
        $rulesStorage = $this->tryGet('Yiisoft\\Rbac\\RulesStorageInterface');
        if ($rulesStorage === null || !method_exists($rulesStorage, 'getAll')) {
            return [];
        }

        try {
            /** @var iterable<object>|array<string, object> $rules */
            $rules = $rulesStorage->getAll();
        } catch (Throwable) {
            return [];
        }

        $result = [];
        foreach ($rules as $key => $rule) {
            if (!is_object($rule)) {
                continue;
            }
            $name = is_string($key) && $key !== '' ? $key : $rule::class;
            $result[] = [
                'name' => $name,
                'type' => 'rbac_rule',
                'priority' => null,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeCurrentUser(object $currentUser): array
    {
        $info = [];

        if (method_exists($currentUser, 'isGuest')) {
            try {
                $info['isGuest'] = (bool) $currentUser->isGuest();
            } catch (Throwable $_) {
                // Unavailable outside a request — fall through without recording the value.
                unset($_);
            }
        }

        if (method_exists($currentUser, 'getId')) {
            try {
                $id = $currentUser->getId();
                if ($id === null || is_scalar($id)) {
                    $info['id'] = $id;
                }
            } catch (Throwable $_) {
                // Identity unavailable — fall through.
                unset($_);
            }
        }

        return $info;
    }

    private function tryGet(string $id): ?object
    {
        if (!$this->container->has($id)) {
            return null;
        }
        try {
            $service = $this->container->get($id);
        } catch (Throwable) {
            return null;
        }

        return is_object($service) ? $service : null;
    }
}
