<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Symfony\Inspector\SymfonyAuthorizationConfigProvider;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use PHPUnit\Framework\TestCase;

final class SymfonyAuthorizationConfigProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider();

        $this->assertInstanceOf(AuthorizationConfigProviderInterface::class, $provider);
    }

    public function testReturnsEmptyDataWhenNoSecurityParametersPresent(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider();

        $this->assertSame([], $provider->getGuards());
        $this->assertSame([], $provider->getRoleHierarchy());
        $this->assertSame([], $provider->getVoters());
        $this->assertSame([], $provider->getSecurityConfig());
    }

    public function testGetGuardsReturnsFirewallsWithCollectedConfig(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.firewalls' => ['main', 'api'],
            'security.firewall.map.config.main.provider' => 'app_user_provider',
            'security.firewall.map.config.main.stateless' => false,
            'security.firewall.map.config.api.provider' => 'api_user_provider',
            'security.firewall.map.config.api.stateless' => true,
            'unrelated.key' => 'ignored',
        ]);

        $guards = $provider->getGuards();

        $this->assertCount(2, $guards);
        $this->assertSame('main', $guards[0]['name']);
        $this->assertSame('app_user_provider', $guards[0]['provider']);
        $this->assertSame('app_user_provider', $guards[0]['config']['provider']);
        $this->assertSame(false, $guards[0]['config']['stateless']);

        $this->assertSame('api', $guards[1]['name']);
        $this->assertSame('api_user_provider', $guards[1]['provider']);
        $this->assertSame(true, $guards[1]['config']['stateless']);
    }

    public function testGetGuardsSkipsInvalidFirewallNames(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.firewalls' => ['main', '', 123, null, 'api'],
        ]);

        $guards = $provider->getGuards();
        $names = array_column($guards, 'name');
        $this->assertSame(['main', 'api'], $names);
    }

    public function testGetRoleHierarchyReadsFromRoleHierarchyParameter(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.role_hierarchy.roles' => [
                'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR'],
                'ROLE_EDITOR' => ['ROLE_USER'],
                'ROLE_USER' => [],
            ],
        ]);

        $this->assertSame(
            [
                'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR'],
                'ROLE_EDITOR' => ['ROLE_USER'],
                'ROLE_USER' => [],
            ],
            $provider->getRoleHierarchy(),
        );
    }

    public function testGetRoleHierarchyIgnoresNonStringChildren(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.role_hierarchy.roles' => [
                'ROLE_ADMIN' => ['ROLE_USER', null, 42, 'ROLE_EDITOR', ''],
            ],
        ]);

        $this->assertSame(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']], $provider->getRoleHierarchy());
    }

    public function testGetVotersListsVoterClassesFromTaggedIterator(): void
    {
        $voter1 = new class {};
        $voter2 = new class {};

        $provider = new SymfonyAuthorizationConfigProvider([], [$voter1, $voter2]);

        $voters = $provider->getVoters();

        $this->assertCount(2, $voters);
        $this->assertSame($voter1::class, $voters[0]['name']);
        $this->assertSame('voter', $voters[0]['type']);
        $this->assertNull($voters[0]['priority']);
        $this->assertSame($voter2::class, $voters[1]['name']);
    }

    public function testGetVotersSkipsNonObjectEntries(): void
    {
        $voter = new class {};

        $provider = new SymfonyAuthorizationConfigProvider([], [$voter, 'not-a-voter', null]);

        $voters = $provider->getVoters();
        $this->assertCount(1, $voters);
        $this->assertSame($voter::class, $voters[0]['name']);
    }

    public function testGetSecurityConfigIncludesAccessControlStrategyAndFirewalls(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.access_control' => [['path' => '/admin', 'roles' => ['ROLE_ADMIN']]],
            'security.access.decision_manager.strategy' => 'affirmative',
            'security.user.provider.concrete.app_user_provider' => ['entity' => ['class' => 'App\\Entity\\User']],
            'security.user.provider.concrete.api_provider' => ['chain' => ['providers' => []]],
            'security.firewalls' => ['main', 'api'],
        ]);

        $config = $provider->getSecurityConfig();

        $this->assertSame([['path' => '/admin', 'roles' => ['ROLE_ADMIN']]], $config['access_control']);
        $this->assertSame('affirmative', $config['access_decision_strategy']);
        $this->assertArrayHasKey('providers', $config);
        $this->assertSame(['api_provider', 'app_user_provider'], array_keys($config['providers']));
        $this->assertSame(['main', 'api'], $config['firewalls']);
    }

    public function testGetSecurityConfigOmitsMissingSections(): void
    {
        $provider = new SymfonyAuthorizationConfigProvider([
            'security.firewalls' => ['main'],
        ]);

        $config = $provider->getSecurityConfig();

        $this->assertArrayNotHasKey('access_control', $config);
        $this->assertArrayNotHasKey('access_decision_strategy', $config);
        $this->assertArrayNotHasKey('providers', $config);
        $this->assertSame(['main'], $config['firewalls']);
    }
}
