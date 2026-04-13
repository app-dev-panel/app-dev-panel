<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit;

use AppDevPanel\McpServer\McpConfig;
use PHPUnit\Framework\TestCase;

final class McpConfigTest extends TestCase
{
    public function testDefaultAllowedInspectorToolsIsNull(): void
    {
        $config = new McpConfig();

        $this->assertNull($config->allowedInspectorTools);
    }

    public function testAllowedInspectorToolsNull(): void
    {
        $config = new McpConfig(allowedInspectorTools: null);

        $this->assertNull($config->allowedInspectorTools);
    }

    public function testAllowedInspectorToolsEmptyList(): void
    {
        $config = new McpConfig(allowedInspectorTools: []);

        $this->assertSame([], $config->allowedInspectorTools);
    }

    public function testAllowedInspectorToolsSpecificList(): void
    {
        $config = new McpConfig(allowedInspectorTools: ['inspect_routes', 'inspect_config']);

        $this->assertSame(['inspect_routes', 'inspect_config'], $config->allowedInspectorTools);
    }
}
