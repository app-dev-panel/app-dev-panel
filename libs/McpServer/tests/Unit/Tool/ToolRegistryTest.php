<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool;

use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class ToolRegistryTest extends TestCase
{
    public function testRegisterAndGet(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('my_tool');

        $registry = new ToolRegistry();
        $registry->register($tool);

        $this->assertSame($tool, $registry->get('my_tool'));
    }

    public function testGetReturnsNullForUnknown(): void
    {
        $registry = new ToolRegistry();

        $this->assertNull($registry->get('nonexistent'));
    }

    public function testListReturnsAllTools(): void
    {
        $tool1 = $this->createMock(ToolInterface::class);
        $tool1->method('getName')->willReturn('tool_a');
        $tool1->method('getDescription')->willReturn('Tool A');
        $tool1->method('getInputSchema')->willReturn(['type' => 'object']);

        $tool2 = $this->createMock(ToolInterface::class);
        $tool2->method('getName')->willReturn('tool_b');
        $tool2->method('getDescription')->willReturn('Tool B');
        $tool2->method('getInputSchema')->willReturn(['type' => 'object']);

        $registry = new ToolRegistry();
        $registry->register($tool1);
        $registry->register($tool2);

        $list = $registry->list();

        $this->assertCount(2, $list);
        $this->assertSame('tool_a', $list[0]['name']);
        $this->assertSame('tool_b', $list[1]['name']);
    }

    public function testListReturnsEmptyForNoTools(): void
    {
        $registry = new ToolRegistry();

        $this->assertSame([], $registry->list());
    }
}
