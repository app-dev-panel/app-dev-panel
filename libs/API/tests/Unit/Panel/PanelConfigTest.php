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
    }

    public function testCustomValues(): void
    {
        $config = new PanelConfig(staticUrl: 'http://localhost:3000', viewerBasePath: '/my-debug');

        $this->assertSame('http://localhost:3000', $config->staticUrl);
        $this->assertSame('/my-debug', $config->viewerBasePath);
    }
}
