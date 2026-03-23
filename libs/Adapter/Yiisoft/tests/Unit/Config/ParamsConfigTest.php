<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

/**
 * Tests that params.php provides correct default values.
 *
 * Critical: ignoredRequests must include /debug/api/* and /inspect/api/*
 * so that Debugger::isRequestIgnored() skips panel's own API requests.
 */
final class ParamsConfigTest extends TestCase
{
    private array $params;

    protected function setUp(): void
    {
        $this->params = require dirname(__DIR__, 3) . '/config/params.php';
    }

    public function testIgnoredRequestsIncludesDebugApi(): void
    {
        $ignored = $this->params['app-dev-panel/yiisoft']['ignoredRequests'];

        $this->assertContains('/debug/api/**', $ignored);
    }

    public function testIgnoredRequestsIncludesInspectApi(): void
    {
        $ignored = $this->params['app-dev-panel/yiisoft']['ignoredRequests'];

        $this->assertContains('/inspect/api/**', $ignored);
    }

    public function testEnabledByDefault(): void
    {
        $this->assertTrue($this->params['app-dev-panel/yiisoft']['enabled']);
    }

    public function testIgnoredCommandsHasDefaults(): void
    {
        $ignored = $this->params['app-dev-panel/yiisoft']['ignoredCommands'];

        $this->assertNotEmpty($ignored);
        $this->assertContains('help', $ignored);
        $this->assertContains('list', $ignored);
    }

    public function testCollectorsArrayNotEmpty(): void
    {
        $this->assertNotEmpty($this->params['app-dev-panel/yiisoft']['collectors']);
    }

    public function testWebCollectorsDefined(): void
    {
        $this->assertArrayHasKey('collectors.web', $this->params['app-dev-panel/yiisoft']);
        $this->assertNotEmpty($this->params['app-dev-panel/yiisoft']['collectors.web']);
    }

    public function testApiConfigExists(): void
    {
        $api = $this->params['app-dev-panel/yiisoft']['api'];

        $this->assertTrue($api['enabled']);
        $this->assertContains('127.0.0.1', $api['allowedIps']);
    }
}
