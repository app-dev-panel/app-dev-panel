<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Panel;

use AppDevPanel\Api\Panel\PanelConfig;
use PHPUnit\Framework\TestCase;

final class PanelConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new PanelConfig();

        $this->assertSame(PanelConfig::DEFAULT_STATIC_URL, $config->staticUrl);
        $this->assertSame('/debug', $config->viewerBasePath);
        $this->assertFalse($config->isDevServer());
    }

    public function testCustomValues(): void
    {
        $config = new PanelConfig(staticUrl: 'http://localhost:3000', viewerBasePath: '/my-debug');

        $this->assertSame('http://localhost:3000', $config->staticUrl);
        $this->assertSame('/my-debug', $config->viewerBasePath);
    }

    public function testIsDevServerDetectsLocalhost(): void
    {
        $this->assertTrue(new PanelConfig(staticUrl: 'http://localhost:3000')->isDevServer());
        $this->assertTrue(new PanelConfig(staticUrl: 'http://127.0.0.1:3000')->isDevServer());
        $this->assertTrue(new PanelConfig(staticUrl: 'http://localhost:5173')->isDevServer());
    }

    public function testIsDevServerReturnsFalseForProduction(): void
    {
        $this->assertFalse(new PanelConfig()->isDevServer());
        $this->assertFalse(new PanelConfig(staticUrl: 'https://cdn.example.com')->isDevServer());
        $this->assertFalse(new PanelConfig(staticUrl: '/assets/panel')->isDevServer());
    }
}
