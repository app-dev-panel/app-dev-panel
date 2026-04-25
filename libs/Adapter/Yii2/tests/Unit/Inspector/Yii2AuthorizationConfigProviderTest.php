<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2AuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use PHPUnit\Framework\TestCase;
use yii\base\Application;
use yii\rbac\Item;
use yii\rbac\ManagerInterface;

final class Yii2AuthorizationConfigProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $provider = new Yii2AuthorizationConfigProvider(null);

        $this->assertInstanceOf(AuthorizationConfigProviderInterface::class, $provider);
    }

    public function testReturnsEmptyDataWhenNoApplication(): void
    {
        $provider = new Yii2AuthorizationConfigProvider(null);

        $this->assertSame([], $provider->getGuards());
        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
        $this->assertSame([], $provider->getSecurityConfig());
    }

    public function testReturnsEmptyGuardsWhenUserComponentMissing(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('has')->willReturn(false);

        $provider = new Yii2AuthorizationConfigProvider($app);

        $this->assertSame([], $provider->getGuards());
    }

    public function testGetGuardsExposesUserComponentIdentityAndConfig(): void
    {
        $user = new FakeUserComponent('app\\models\\User', true);
        $user->loginUrl = ['site/login'];
        $user->enableSession = true;
        $user->authTimeout = 3600;
        $user->enableAutoLogin = false;

        $app = $this->appWith(['user' => $user]);
        $provider = new Yii2AuthorizationConfigProvider($app);

        $guards = $provider->getGuards();

        $this->assertCount(1, $guards);
        $this->assertSame('user', $guards[0]['name']);
        $this->assertSame('app\\models\\User', $guards[0]['provider']);
        $this->assertSame(
            [
                'loginUrl' => ['site/login'],
                'enableSession' => true,
                'authTimeout' => 3600,
                'absoluteAuthTimeout' => null,
                'enableAutoLogin' => false,
            ],
            $guards[0]['config'],
        );
    }

    public function testGetRoleHierarchyBuildsMapFromAuthManager(): void
    {
        $admin = $this->createMock(Item::class);
        $editor = $this->createMock(Item::class);
        $user = $this->createMock(Item::class);

        $manager = $this->createMock(ManagerInterface::class);
        $manager
            ->method('getRoles')
            ->willReturn([
                'admin' => $admin,
                'editor' => $editor,
                'user' => $user,
            ]);
        $manager
            ->method('getChildren')
            ->willReturnCallback(static fn(string $name): array => match ($name) {
                'admin' => ['editor' => $editor, 'user' => $user],
                'editor' => ['user' => $user],
                default => [],
            });

        $provider = new Yii2AuthorizationConfigProvider($this->appWith(['authManager' => $manager]));

        $this->assertSame(
            [
                'admin' => ['editor', 'user'],
                'editor' => ['user'],
                'user' => [],
            ],
            $provider->getRoleHierarchy(),
        );
    }

    public function testGetVotersIncludesRolesPermissionsAndRules(): void
    {
        $role = $this->createMock(Item::class);
        $permission = $this->createMock(Item::class);
        $rule = new class {};

        $manager = $this->createMock(ManagerInterface::class);
        $manager->method('getRoles')->willReturn(['admin' => $role]);
        $manager->method('getPermissions')->willReturn(['createPost' => $permission]);
        $manager->method('getRules')->willReturn(['authorRule' => $rule]);

        $provider = new Yii2AuthorizationConfigProvider($this->appWith(['authManager' => $manager]));

        $voters = $provider->getVoters();

        $this->assertSame(
            [
                ['name' => 'admin', 'type' => 'role', 'priority' => null],
                ['name' => 'createPost', 'type' => 'permission', 'priority' => null],
                ['name' => $rule::class, 'type' => 'rule', 'priority' => null],
            ],
            $voters,
        );
    }

    public function testGetVotersReturnsEmptyWhenAuthManagerMissing(): void
    {
        $provider = new Yii2AuthorizationConfigProvider($this->appWith([]));

        $this->assertSame([], $provider->getVoters());
    }

    public function testGetSecurityConfigIncludesUserSnapshotAndAuthManager(): void
    {
        $user = new FakeUserComponent('app\\models\\User', true);
        $manager = new FakeAuthManager(['admin']);

        $provider = new Yii2AuthorizationConfigProvider($this->appWith([
            'user' => $user,
            'authManager' => $manager,
        ]));

        $this->assertSame(
            [
                'user' => ['identityClass' => 'app\\models\\User', 'isGuest' => true, 'id' => null],
                'authManager' => ['class' => FakeAuthManager::class, 'defaultRoles' => ['admin']],
            ],
            $provider->getSecurityConfig(),
        );
    }

    public function testGracefullyHandlesAuthManagerThrowing(): void
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->method('getRoles')->willThrowException(new \RuntimeException('db not migrated'));
        $manager->method('getPermissions')->willThrowException(new \RuntimeException('db not migrated'));
        $manager->method('getRules')->willThrowException(new \RuntimeException('db not migrated'));

        $provider = new Yii2AuthorizationConfigProvider($this->appWith(['authManager' => $manager]));

        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
    }

    /**
     * @param array<string, object> $components
     */
    private function appWith(array $components): Application
    {
        $app = $this->createMock(Application::class);
        $app->method('has')->willReturnCallback(static fn(string $id): bool => array_key_exists($id, $components));
        $app->method('get')->willReturnCallback(static function (string $id) use ($components): ?object {
            return $components[$id] ?? null;
        });
        return $app;
    }
}

final class FakeUserComponent
{
    public mixed $loginUrl = null;

    public bool $enableSession = false;

    public ?int $authTimeout = null;

    public ?int $absoluteAuthTimeout = null;

    public bool $enableAutoLogin = false;

    public int|string|null $id = null;

    public function __construct(
        public string $identityClass,
        public bool $isGuest,
    ) {}
}

final class FakeAuthManager implements ManagerInterface
{
    /**
     * @param list<string> $defaultRoles
     */
    public function __construct(
        public array $defaultRoles = [],
    ) {}

    public function checkAccess($userId, $permissionName, $params = []): bool
    {
        return false;
    }

    public function createRole($name)
    {
        return null;
    }

    public function createPermission($name)
    {
        return null;
    }

    public function add($object): bool
    {
        return false;
    }

    public function remove($object): bool
    {
        return false;
    }

    public function update($name, $object): bool
    {
        return false;
    }

    public function getRole($name)
    {
        return null;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getRolesByUser($userId): array
    {
        return [];
    }

    public function getChildRoles($roleName): array
    {
        return [];
    }

    public function getPermission($name)
    {
        return null;
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getPermissionsByRole($roleName): array
    {
        return [];
    }

    public function getPermissionsByUser($userId): array
    {
        return [];
    }

    public function getRule($name)
    {
        return null;
    }

    public function getRules(): array
    {
        return [];
    }

    public function canAddChild($parent, $child): bool
    {
        return false;
    }

    public function addChild($parent, $child): bool
    {
        return false;
    }

    public function removeChild($parent, $child): bool
    {
        return false;
    }

    public function removeChildren($parent): bool
    {
        return false;
    }

    public function hasChild($parent, $child): bool
    {
        return false;
    }

    public function getChildren($name): array
    {
        return [];
    }

    public function assign($role, $userId)
    {
        return null;
    }

    public function revoke($role, $userId): bool
    {
        return false;
    }

    public function revokeAll($userId): bool
    {
        return false;
    }

    public function getAssignment($roleName, $userId)
    {
        return null;
    }

    public function getAssignments($userId): array
    {
        return [];
    }

    public function getUserIdsByRole($roleName): array
    {
        return [];
    }

    public function removeAll(): void {}

    public function removeAllPermissions(): void {}

    public function removeAllRoles(): void {}

    public function removeAllRules(): void {}

    public function removeAllAssignments(): void {}
}
