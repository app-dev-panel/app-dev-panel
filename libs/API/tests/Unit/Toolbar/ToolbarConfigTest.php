<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Toolbar;

use AppDevPanel\Api\Toolbar\ToolbarConfig;
use PHPUnit\Framework\TestCase;

final class ToolbarConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new ToolbarConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame('', $config->staticUrl);
    }

    public function testDisabled(): void
    {
        $config = new ToolbarConfig(enabled: false);

        $this->assertFalse($config->enabled);
    }

    public function testCustomStaticUrl(): void
    {
        $config = new ToolbarConfig(staticUrl: 'http://localhost:3001');

        $this->assertSame('http://localhost:3001', $config->staticUrl);
    }
}
