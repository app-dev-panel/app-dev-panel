<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\McpConfig;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class McpToolRegistryFactoryTest extends TestCase
{
    public function testCreateReturnsRegistryWithAllTools(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $registry = McpToolRegistryFactory::create($storage);

        $this->assertInstanceOf(ToolRegistry::class, $registry);

        $tools = $registry->list();
        $this->assertCount(6, $tools);

        $names = array_column($tools, 'name');
        $this->assertContains('list_debug_entries', $names);
        $this->assertContains('view_debug_entry', $names);
        $this->assertContains('search_logs', $names);
        $this->assertContains('analyze_exception', $names);
        $this->assertContains('view_database_queries', $names);
        $this->assertContains('view_timeline', $names);
    }

    public function testCreateWithInspectorRegistersAllInspectorTools(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $inspector = $this->createMock(InspectorInterface::class);

        $registry = McpToolRegistryFactory::create($storage, $inspector);

        $tools = $registry->list();
        $this->assertCount(9, $tools);

        $names = array_column($tools, 'name');
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_CONFIG, $names);
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_ROUTES, $names);
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_SCHEMA, $names);
    }

    public function testCreateWithInspectorAndNullConfigRegistersAllInspectorTools(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $inspector = $this->createMock(InspectorInterface::class);

        $registry = McpToolRegistryFactory::create($storage, $inspector, new McpConfig());

        $tools = $registry->list();
        $names = array_column($tools, 'name');
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_CONFIG, $names);
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_ROUTES, $names);
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_SCHEMA, $names);
    }

    public function testCreateWithEmptyAllowedInspectorToolsRegistersNoInspectorTools(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $inspector = $this->createMock(InspectorInterface::class);

        $registry = McpToolRegistryFactory::create($storage, $inspector, new McpConfig(allowedInspectorTools: []));

        $tools = $registry->list();
        $this->assertCount(6, $tools);

        $names = array_column($tools, 'name');
        $this->assertNotContains(McpToolRegistryFactory::TOOL_INSPECT_CONFIG, $names);
        $this->assertNotContains(McpToolRegistryFactory::TOOL_INSPECT_ROUTES, $names);
        $this->assertNotContains(McpToolRegistryFactory::TOOL_INSPECT_SCHEMA, $names);
    }

    public function testCreateWithSpecificAllowedInspectorTools(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $inspector = $this->createMock(InspectorInterface::class);

        $registry = McpToolRegistryFactory::create(
            $storage,
            $inspector,
            new McpConfig(allowedInspectorTools: [McpToolRegistryFactory::TOOL_INSPECT_ROUTES]),
        );

        $tools = $registry->list();
        $names = array_column($tools, 'name');
        $this->assertContains(McpToolRegistryFactory::TOOL_INSPECT_ROUTES, $names);
        $this->assertNotContains(McpToolRegistryFactory::TOOL_INSPECT_CONFIG, $names);
        $this->assertNotContains(McpToolRegistryFactory::TOOL_INSPECT_SCHEMA, $names);
    }

    public function testCreateWithoutInspectorIgnoresConfig(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        // Even with config specifying tools, no inspector tools should be added if no inspector
        $registry = McpToolRegistryFactory::create(
            $storage,
            null,
            new McpConfig(allowedInspectorTools: null),
        );

        $tools = $registry->list();
        $this->assertCount(6, $tools);
    }

    public function testToolNameConstants(): void
    {
        $this->assertSame('inspect_config', McpToolRegistryFactory::TOOL_INSPECT_CONFIG);
        $this->assertSame('inspect_routes', McpToolRegistryFactory::TOOL_INSPECT_ROUTES);
        $this->assertSame('inspect_database_schema', McpToolRegistryFactory::TOOL_INSPECT_SCHEMA);
    }
}
