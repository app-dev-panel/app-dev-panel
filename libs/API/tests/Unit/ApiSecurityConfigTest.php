<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ApiSecurityConfig;
use PHPUnit\Framework\TestCase;

final class ApiSecurityConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new ApiSecurityConfig();

        $this->assertSame(['127.0.0.1', '::1'], $config->allowedIps);
        $this->assertSame('', $config->authToken);
        $this->assertSame([], $config->allowedHosts);
        $this->assertSame(['127.0.0.1', 'localhost'], $config->requestReplayAllowedHosts);
    }

    public function testCustomValues(): void
    {
        $token = str_repeat('x', 32);
        $config = new ApiSecurityConfig(
            allowedIps: ['10.0.0.1'],
            authToken: $token,
            allowedHosts: ['example.com'],
            requestReplayAllowedHosts: ['staging.local'],
        );

        $this->assertSame(['10.0.0.1'], $config->allowedIps);
        $this->assertSame($token, $config->authToken);
        $this->assertSame(['example.com'], $config->allowedHosts);
        $this->assertSame(['staging.local'], $config->requestReplayAllowedHosts);
    }
}
