<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Spiral\Inspector\SpiralAuthorizationConfigProvider;
use PHPUnit\Framework\TestCase;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Core\Container;

final class SpiralAuthorizationConfigProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        SpiralStubsBootstrap::install();
    }

    public function testGuardsEmptyWhenAuthNotBound(): void
    {
        $provider = new SpiralAuthorizationConfigProvider(new Container());

        self::assertSame([], $provider->getGuards());
        self::assertSame([], $provider->getSecurityConfig());
    }

    public function testGuardsExposeTokenStorageAndOptionalActorProvider(): void
    {
        $tokenStorage = new class implements TokenStorageInterface {
            public function load(string $id): ?object
            {
                return null;
            }
        };
        $actorProvider = new class implements ActorProviderInterface {
            public function getActor(object $token): ?object
            {
                return null;
            }
        };

        $container = new Container();
        $container->bindSingleton(TokenStorageInterface::class, $tokenStorage);
        $container->bindSingleton(ActorProviderInterface::class, $actorProvider);

        $provider = new SpiralAuthorizationConfigProvider($container);
        $guards = $provider->getGuards();

        self::assertCount(1, $guards);
        self::assertSame('spiral', $guards[0]['name']);
        self::assertSame($tokenStorage::class, $guards[0]['config']['tokenStorage']);
        self::assertSame($actorProvider::class, $guards[0]['config']['actorProvider']);
        self::assertSame($actorProvider::class, $guards[0]['provider']);

        self::assertSame(['tokenStorage' => $tokenStorage::class], $provider->getSecurityConfig());
    }

    public function testRoleHierarchyAndVotersAlwaysEmpty(): void
    {
        $provider = new SpiralAuthorizationConfigProvider(new Container());

        self::assertSame([], $provider->getRoleHierarchy());
        self::assertSame([], $provider->getVoters());
    }
}
