<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii3\Inspector\Yii3AuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Yii3AuthorizationConfigProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $provider = new Yii3AuthorizationConfigProvider($this->emptyContainer());

        $this->assertInstanceOf(AuthorizationConfigProviderInterface::class, $provider);
    }

    public function testReturnsEmptyDataWhenNoYiiAuthServicesPresent(): void
    {
        $provider = new Yii3AuthorizationConfigProvider($this->emptyContainer());

        $this->assertSame([], $provider->getGuards());
        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
        $this->assertSame([], $provider->getSecurityConfig());
    }

    public function testGetSecurityConfigReturnsParamsSubtreesWhenProvided(): void
    {
        $params = [
            'user' => ['guestId' => null, 'authTimeout' => 3600],
            'rbac' => ['itemFile' => '@runtime/rbac/items.php'],
            'auth' => ['methods' => ['bearer']],
            // Irrelevant keys are ignored.
            'unrelated' => 'ignored',
        ];

        $provider = new Yii3AuthorizationConfigProvider($this->emptyContainer(), $params);

        $this->assertSame(
            [
                'user' => ['guestId' => null, 'authTimeout' => 3600],
                'rbac' => ['itemFile' => '@runtime/rbac/items.php'],
                'auth' => ['methods' => ['bearer']],
            ],
            $provider->getSecurityConfig(),
        );
    }

    public function testGetSecurityConfigIncludesCurrentUserWhenAvailable(): void
    {
        $currentUser = new class {
            public function isGuest(): bool
            {
                return false;
            }

            public function getId(): string
            {
                return '42';
            }
        };

        $provider = new Yii3AuthorizationConfigProvider($this->containerWith([
            'Yiisoft\\User\\CurrentUser' => $currentUser,
        ]));

        $this->assertSame(['currentUser' => ['isGuest' => false, 'id' => '42']], $provider->getSecurityConfig());
    }

    public function testGetRoleHierarchyBuildsMapFromItemsStorage(): void
    {
        $admin = $this->namedItem('admin');
        $editor = $this->namedItem('editor');
        $user = $this->namedItem('user');

        $itemsStorage = new class($admin, $editor, $user) {
            /**
             * @var array<string, list<object>>
             */
            private array $children;

            /**
             * @var list<object>
             */
            private array $all;

            public function __construct(object $admin, object $editor, object $user)
            {
                $this->all = [$admin, $editor, $user];
                $this->children = [
                    'admin' => [$editor, $user],
                    'editor' => [$user],
                    'user' => [],
                ];
            }

            public function getAll(): array
            {
                return $this->all;
            }

            public function getChildren(string $name): array
            {
                return $this->children[$name] ?? [];
            }
        };

        $provider = new Yii3AuthorizationConfigProvider($this->containerWith([
            'Yiisoft\\Rbac\\ItemsStorageInterface' => $itemsStorage,
        ]));

        $this->assertSame(
            [
                'admin' => ['editor', 'user'],
                'editor' => ['user'],
                'user' => [],
            ],
            $provider->getRoleHierarchy(),
        );
    }

    public function testGetRoleHierarchyUsesGetDirectChildrenForRbacV2(): void
    {
        $admin = $this->namedItem('admin');
        $editor = $this->namedItem('editor');

        $itemsStorage = new class($admin, $editor) {
            /**
             * @var list<object>
             */
            private array $all;

            /**
             * @var array<string, list<object>>
             */
            private array $children;

            public function __construct(object $admin, object $editor)
            {
                $this->all = [$admin, $editor];
                $this->children = ['admin' => [$editor], 'editor' => []];
            }

            public function getAll(): array
            {
                return $this->all;
            }

            public function getDirectChildren(string $name): array
            {
                return $this->children[$name] ?? [];
            }
        };

        $provider = new Yii3AuthorizationConfigProvider($this->containerWith([
            'Yiisoft\\Rbac\\ItemsStorageInterface' => $itemsStorage,
        ]));

        $this->assertSame(['admin' => ['editor'], 'editor' => []], $provider->getRoleHierarchy());
    }

    public function testGetGuardsListsAuthenticationMethodsWithoutDuplicates(): void
    {
        $bearer = new class {
            private string $realm = 'api';

            private string $headerName = 'Authorization';
        };

        // Same instance exposed both by its concrete class and by the interface id —
        // the provider must not produce duplicate entries.
        $provider = new Yii3AuthorizationConfigProvider($this->containerWith([
            'Yiisoft\\Auth\\Method\\HttpBearer' => $bearer,
            'Yiisoft\\Auth\\AuthenticationMethodInterface' => $bearer,
        ]));

        $guards = $provider->getGuards();
        $this->assertCount(1, $guards);
        $this->assertSame($bearer::class, $guards[0]['name']);
        $this->assertSame($bearer::class, $guards[0]['provider']);
        $this->assertSame(['realm' => 'api', 'headerName' => 'Authorization'], $guards[0]['config']);
    }

    public function testGetVotersIncludesAccessCheckerAndRbacRules(): void
    {
        $accessChecker = new class {
            public function userHasPermission(
                int|string|null $userId,
                string $permissionName,
                array $parameters = [],
            ): bool {
                return false;
            }
        };

        $ruleA = new class {};
        $ruleB = new class {};

        $rulesStorage = new class($ruleA, $ruleB) {
            /**
             * @var array<string, object>
             */
            private array $rules;

            public function __construct(object $a, object $b)
            {
                $this->rules = ['ruleA' => $a, 'ruleB' => $b];
            }

            public function getAll(): array
            {
                return $this->rules;
            }
        };

        $provider = new Yii3AuthorizationConfigProvider($this->containerWith([
            'Yiisoft\\Access\\AccessCheckerInterface' => $accessChecker,
            'Yiisoft\\Rbac\\RulesStorageInterface' => $rulesStorage,
        ]));

        $voters = $provider->getVoters();

        $this->assertCount(3, $voters);
        $this->assertSame('access_checker', $voters[0]['type']);
        $this->assertSame($accessChecker::class, $voters[0]['name']);
        $this->assertNull($voters[0]['priority']);

        $this->assertSame('rbac_rule', $voters[1]['type']);
        $this->assertSame('ruleA', $voters[1]['name']);
        $this->assertSame('rbac_rule', $voters[2]['type']);
        $this->assertSame('ruleB', $voters[2]['name']);
    }

    public function testGracefullyHandlesContainerExceptions(): void
    {
        $container = new class implements ContainerInterface {
            public function has(string $id): bool
            {
                return true;
            }

            public function get(string $id): mixed
            {
                throw new class('boom') extends \RuntimeException implements NotFoundExceptionInterface {};
            }
        };

        $provider = new Yii3AuthorizationConfigProvider($container);

        $this->assertSame([], $provider->getGuards());
        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
        $this->assertSame([], $provider->getSecurityConfig());
    }

    private function emptyContainer(): ContainerInterface
    {
        return $this->containerWith([]);
    }

    /**
     * @param array<string, object> $services
     */
    private function containerWith(array $services): ContainerInterface
    {
        return new class($services) implements ContainerInterface {
            /**
             * @param array<string, object> $services
             */
            public function __construct(
                private array $services,
            ) {}

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }

            public function get(string $id): object
            {
                if (!array_key_exists($id, $this->services)) {
                    throw new class("Service {$id} not found") extends \RuntimeException implements
                        NotFoundExceptionInterface {};
                }
                return $this->services[$id];
            }
        };
    }

    private function namedItem(string $name): object
    {
        return new class($name) {
            public function __construct(
                private string $name,
            ) {}

            public function getName(): string
            {
                return $this->name;
            }
        };
    }
}
