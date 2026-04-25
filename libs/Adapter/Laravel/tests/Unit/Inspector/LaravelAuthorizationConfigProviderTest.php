<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

final class LaravelAuthorizationConfigProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => null]));

        $this->assertInstanceOf(AuthorizationConfigProviderInterface::class, $provider);
    }

    public function testReturnsEmptyDataWhenNothingBound(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('bound')->willReturn(false);

        $provider = new LaravelAuthorizationConfigProvider($app);

        $this->assertSame([], $provider->getGuards());
        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
        $this->assertSame([], $provider->getSecurityConfig());
    }

    public function testGetGuardsMapsGuardsConfigAndResolvesProviderModel(): void
    {
        $config = new Repository([
            'auth' => [
                'guards' => [
                    'web' => ['driver' => 'session', 'provider' => 'users'],
                    'api' => ['driver' => 'token', 'provider' => 'api_users'],
                    'malformed' => 'not-an-array',
                    '' => ['driver' => 'ignored'],
                ],
                'providers' => [
                    'users' => ['driver' => 'eloquent', 'model' => 'App\\Models\\User'],
                    'api_users' => ['driver' => 'database'],
                ],
            ],
        ]);
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => $config]));

        $guards = $provider->getGuards();

        $this->assertCount(2, $guards);
        $this->assertSame('web', $guards[0]['name']);
        $this->assertSame('App\\Models\\User', $guards[0]['provider']);
        $this->assertSame(['driver' => 'session', 'provider' => 'users'], $guards[0]['config']);

        $this->assertSame('api', $guards[1]['name']);
        $this->assertSame('database', $guards[1]['provider']);
    }

    public function testGetGuardsReturnsEmptyArrayWhenAuthGuardsMissing(): void
    {
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => new Repository(['app' => [
            'name' => 'X',
        ]])]));

        $this->assertSame([], $provider->getGuards());
    }

    public function testGetRoleHierarchyReturnsEmptyWhenSpatieNotInstalled(): void
    {
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => new Repository([])]));

        // Spatie\Permission\Models\Role does not exist in the test environment.
        $this->assertSame([], $provider->getRoleHierarchy());
    }

    public function testGetVotersListsAbilitiesAndPoliciesFromGate(): void
    {
        $gate = new FakeLaravelGate(abilities: [
            'edit-post' => fn(): bool => true,
            'delete-post' => fn(): bool => true,
            '' => fn(): bool => false,
        ], policies: [
            'App\\Models\\Post' => 'App\\Policies\\PostPolicy',
            'App\\Models\\User' => 'App\\Policies\\UserPolicy',
        ]);

        $provider = new LaravelAuthorizationConfigProvider($this->appWith([
            'Illuminate\\Contracts\\Auth\\Access\\Gate' => $gate,
            'config' => new Repository([]),
        ]));

        $voters = $provider->getVoters();

        $this->assertSame(
            [
                ['name' => 'edit-post', 'type' => 'ability', 'priority' => null],
                ['name' => 'delete-post', 'type' => 'ability', 'priority' => null],
                ['name' => 'App\\Policies\\PostPolicy', 'type' => 'policy', 'priority' => null],
                ['name' => 'App\\Policies\\UserPolicy', 'type' => 'policy', 'priority' => null],
            ],
            $voters,
        );
    }

    public function testGetVotersReturnsEmptyWhenGateNotBound(): void
    {
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => new Repository([])]));

        $this->assertSame([], $provider->getVoters());
    }

    public function testGetSecurityConfigReturnsSelectedAuthSubsections(): void
    {
        $config = new Repository([
            'auth' => [
                'defaults' => ['guard' => 'web', 'passwords' => 'users'],
                'providers' => ['users' => ['driver' => 'eloquent']],
                'passwords' => ['users' => ['table' => 'password_resets']],
                'password_timeout' => 10_800,
                'guards' => ['ignored_here' => []],
            ],
        ]);
        $provider = new LaravelAuthorizationConfigProvider($this->appWith(['config' => $config]));

        $this->assertSame(
            [
                'defaults' => ['guard' => 'web', 'passwords' => 'users'],
                'providers' => ['users' => ['driver' => 'eloquent']],
                'passwords' => ['users' => ['table' => 'password_resets']],
                'password_timeout' => 10_800,
            ],
            $provider->getSecurityConfig(),
        );
    }

    /**
     * @param array<string, mixed> $bindings
     */
    private function appWith(array $bindings): Application
    {
        $app = $this->createMock(Application::class);
        $app->method('bound')->willReturnCallback(
            static fn(string $abstract): bool => (
                array_key_exists($abstract, $bindings)
                && $bindings[$abstract] !== null
            ),
        );
        $app->method('make')->willReturnCallback(static function (string $abstract) use ($bindings): mixed {
            return $bindings[$abstract] ?? null;
        });
        return $app;
    }
}

final class FakeLaravelGate
{
    /**
     * @param array<string, callable> $abilities
     * @param array<string, string>   $policies
     */
    public function __construct(
        public array $abilities,
        public array $policies,
    ) {}
}
