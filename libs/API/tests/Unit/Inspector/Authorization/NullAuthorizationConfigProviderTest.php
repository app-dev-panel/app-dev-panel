<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Authorization;

use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider;
use PHPUnit\Framework\TestCase;

final class NullAuthorizationConfigProviderTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $provider = new NullAuthorizationConfigProvider();

        $this->assertInstanceOf(AuthorizationConfigProviderInterface::class, $provider);
    }

    public function testGetGuardsReturnsEmptyArray(): void
    {
        $provider = new NullAuthorizationConfigProvider();

        $this->assertSame([], $provider->getGuards());
    }

    public function testGetRoleHierarchyReturnsEmptyArray(): void
    {
        $provider = new NullAuthorizationConfigProvider();

        $this->assertSame([], $provider->getRoleHierarchy());
    }

    public function testGetVotersReturnsEmptyArray(): void
    {
        $provider = new NullAuthorizationConfigProvider();

        $this->assertSame([], $provider->getVoters());
    }

    public function testGetSecurityConfigReturnsEmptyArray(): void
    {
        $provider = new NullAuthorizationConfigProvider();

        $this->assertSame([], $provider->getSecurityConfig());
    }
}
