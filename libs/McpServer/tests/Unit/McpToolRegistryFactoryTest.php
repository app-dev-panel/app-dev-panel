<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\McpToolRegistryFactory;
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
}
