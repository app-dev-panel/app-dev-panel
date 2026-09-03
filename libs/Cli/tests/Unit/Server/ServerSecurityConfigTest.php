<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Tests\Unit\Server;

use AppDevPanel\Cli\Server\ServerSecurityConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServerSecurityConfigTest extends TestCase
{
    public function testDefaultsToLoopbackOnlyAndStrict(): void
    {
        $config = ServerSecurityConfig::fromEnvironment([]);

        $this->assertSame(['127.0.0.1', '::1'], $config->allowedIps);
        $this->assertSame('', $config->authToken);
        $this->assertTrue($config->isStrict());
    }

    public function testAllowedIpsFromEnvironment(): void
    {
        $config = ServerSecurityConfig::fromEnvironment([
            ServerSecurityConfig::ENV_ALLOWED_IPS => ' 10.0.0.5, 10.0.0.6 ,,10.0.0.5',
        ]);

        $this->assertSame(['10.0.0.5', '10.0.0.6'], $config->allowedIps);
        $this->assertTrue($config->isStrict());
    }

    public function testWildcardAllowsEveryClient(): void
    {
        $config = ServerSecurityConfig::fromEnvironment([
            ServerSecurityConfig::ENV_ALLOWED_IPS => '*',
            ServerSecurityConfig::ENV_AUTH_TOKEN => 'secret',
        ]);

        $this->assertSame([], $config->allowedIps);
        $this->assertFalse($config->isStrict());
        $this->assertSame('secret', $config->authToken);
    }

    public function testMalformedEnvironmentValuesFallBackToLoopback(): void
    {
        $this->assertSame(['127.0.0.1', '::1'], ServerSecurityConfig::parseAllowedIps(' , ,'));

        $config = ServerSecurityConfig::fromEnvironment([
            ServerSecurityConfig::ENV_ALLOWED_IPS => false,
            ServerSecurityConfig::ENV_AUTH_TOKEN => false,
        ]);

        $this->assertSame(['127.0.0.1', '::1'], $config->allowedIps);
        $this->assertSame('', $config->authToken);
    }

    #[DataProvider('provideNonLoopbackHosts')]
    public function testNonLoopbackBindWithoutTokenIsRefused(string $host): void
    {
        $reason = ServerSecurityConfig::fromEnvironment([])->unsafeBindReason($host);

        $this->assertNotNull($reason);
        $this->assertStringContainsString(ServerSecurityConfig::ENV_AUTH_TOKEN, $reason);
        $this->assertStringContainsString($host, $reason);
    }

    public static function provideNonLoopbackHosts(): iterable
    {
        yield 'any' => ['0.0.0.0'];
        yield 'lan' => ['192.168.1.20'];
        yield 'ipv6 any' => ['[::]'];
        yield 'hostname' => ['dev.example.internal'];
    }

    public function testBindIsAcceptedWithTokenOrOnLoopback(): void
    {
        $withToken = ServerSecurityConfig::fromEnvironment([ServerSecurityConfig::ENV_AUTH_TOKEN => 'tok']);
        $this->assertNull($withToken->unsafeBindReason('0.0.0.0'));
        $this->assertSame(['127.0.0.1', '::1'], $withToken->allowedIps);

        $this->assertNull(ServerSecurityConfig::fromEnvironment([])->unsafeBindReason('127.0.0.1'));
        $this->assertNull(ServerSecurityConfig::fromEnvironment([])->unsafeBindReason('localhost'));
    }

    #[DataProvider('provideHosts')]
    public function testIsLoopbackHost(string $host, bool $expected): void
    {
        $this->assertSame($expected, ServerSecurityConfig::isLoopbackHost($host));
    }

    public static function provideHosts(): iterable
    {
        yield 'ipv4 loopback' => ['127.0.0.1', true];
        yield 'ipv4 loopback range' => ['127.0.0.53', true];
        yield 'ipv6 loopback' => ['::1', true];
        yield 'bracketed ipv6 loopback' => ['[::1]', true];
        yield 'localhost' => ['LocalHost', true];
        yield 'any' => ['0.0.0.0', false];
        yield 'lan' => ['10.1.2.3', false];
        yield 'empty' => ['', false];
    }

    public function testDescribesAndSerialisesConfigurationWithoutLeakingTheToken(): void
    {
        $described = new ServerSecurityConfig(['10.0.0.1', '10.0.0.2'], 'super-secret');
        $this->assertSame('10.0.0.1, 10.0.0.2', $described->describeAllowedIps());
        $this->assertSame('(set)', $described->describeAuthToken());
        $this->assertSame('(everyone)', new ServerSecurityConfig([], '')->describeAllowedIps());
        $this->assertSame('(none)', new ServerSecurityConfig([], '')->describeAuthToken());

        $config = new ServerSecurityConfig(['10.0.0.1'], 'tok');

        $env = $config->toEnvironment('0.0.0.0');
        $this->assertSame(
            [
                ServerSecurityConfig::ENV_BIND_HOST => '0.0.0.0',
                ServerSecurityConfig::ENV_ALLOWED_IPS => '10.0.0.1',
                ServerSecurityConfig::ENV_AUTH_TOKEN => 'tok',
            ],
            $env,
        );

        $restored = ServerSecurityConfig::fromEnvironment($env);
        $this->assertSame(['10.0.0.1'], $restored->allowedIps);
        $this->assertSame('tok', $restored->authToken);

        $everyone = new ServerSecurityConfig([], 'tok')->toEnvironment('0.0.0.0');
        $this->assertSame('*', $everyone[ServerSecurityConfig::ENV_ALLOWED_IPS]);
        $this->assertSame([], ServerSecurityConfig::fromEnvironment($everyone)->allowedIps);
    }
}
