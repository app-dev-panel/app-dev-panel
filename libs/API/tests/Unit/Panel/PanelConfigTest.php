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

        $this->assertSame('/debug/static', PanelConfig::DEFAULT_STATIC_URL);
        $this->assertSame(PanelConfig::DEFAULT_STATIC_URL, $config->staticUrl);
        $this->assertSame('/debug', $config->viewerBasePath);
    }

    public function testCdnUrlConstantPointsAtGitHubPages(): void
    {
        $this->assertSame('https://app-dev-panel.github.io/app-dev-panel', PanelConfig::CDN_STATIC_URL);
    }

    public function testCustomValues(): void
    {
        $config = new PanelConfig(staticUrl: '/bundles/appdevpanel', viewerBasePath: '/my-debug');

        $this->assertSame('/bundles/appdevpanel', $config->staticUrl);
        $this->assertSame('/my-debug', $config->viewerBasePath);
    }
}
