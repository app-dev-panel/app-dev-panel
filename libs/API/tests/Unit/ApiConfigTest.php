<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ApiConfig;
use AppDevPanel\Api\ApiExtensionsConfig;
use AppDevPanel\Api\ApiSecurityConfig;
use PHPUnit\Framework\TestCase;

final class ApiConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new ApiConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame('', $config->storagePath);
        $this->assertInstanceOf(ApiSecurityConfig::class, $config->security);
        $this->assertInstanceOf(ApiExtensionsConfig::class, $config->extensions);
    }

    public function testCustomValues(): void
    {
        $security = new ApiSecurityConfig(allowedIps: ['10.0.0.1']);
        $extensions = new ApiExtensionsConfig(params: ['key' => 'value']);

        $config = new ApiConfig(
            enabled: false,
            security: $security,
            extensions: $extensions,
            storagePath: '/tmp/debug',
        );

        $this->assertFalse($config->enabled);
        $this->assertSame('/tmp/debug', $config->storagePath);
        $this->assertSame(['10.0.0.1'], $config->security->allowedIps);
        $this->assertSame(['key' => 'value'], $config->extensions->params);
    }
}
