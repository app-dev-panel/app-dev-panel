<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit;

use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class McpServerTest extends TestCase
{
    public function testInitializeReturnsCapabilities(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ]);

        $this->assertSame(1, $response['id']);
        $this->assertSame('2024-11-05', $response['result']['protocolVersion']);
        $this->assertSame('adp-mcp', $response['result']['serverInfo']['name']);
        $this->assertArrayHasKey('tools', $response['result']['capabilities']);
    }

    public function testPingReturnsEmptyResult(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'ping',
        ]);

        $this->assertSame(2, $response['id']);
        $this->assertSame([], $response['result']);
    }

    public function testToolsListReturnsRegisteredTools(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('test_tool');
        $tool->method('getDescription')->willReturn('A test tool');
        $tool->method('getInputSchema')->willReturn(['type' => 'object', 'properties' => []]);

        $registry = new ToolRegistry();
        $registry->register($tool);

        $server = $this->createServer($registry);

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/list',
        ]);

        $this->assertSame(3, $response['id']);
        $this->assertCount(1, $response['result']['tools']);
        $this->assertSame('test_tool', $response['result']['tools'][0]['name']);
    }

    public function testToolsCallExecutesTool(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('test_tool');
        $tool
            ->method('execute')
            ->with(['arg1' => 'value1'])
            ->willReturn([
                'content' => [['type' => 'text', 'text' => 'result']],
            ]);

        $registry = new ToolRegistry();
        $registry->register($tool);

        $server = $this->createServer($registry);

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => ['name' => 'test_tool', 'arguments' => ['arg1' => 'value1']],
        ]);

        $this->assertSame(4, $response['id']);
        $this->assertSame('result', $response['result']['content'][0]['text']);
    }

    public function testToolsCallWithUnknownToolReturnsError(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => ['name' => 'nonexistent'],
        ]);

        $this->assertSame(5, $response['id']);
        $this->assertTrue($response['result']['isError']);
        $this->assertStringContainsString('nonexistent', $response['result']['content'][0]['text']);
    }

    public function testToolsCallCatchesExceptions(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('failing_tool');
        $tool->method('execute')->willThrowException(new \RuntimeException('Something broke'));

        $registry = new ToolRegistry();
        $registry->register($tool);

        $server = $this->createServer($registry);

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'tools/call',
            'params' => ['name' => 'failing_tool', 'arguments' => []],
        ]);

        $this->assertSame(6, $response['id']);
        $this->assertTrue($response['result']['isError']);
        $this->assertStringContainsString('Something broke', $response['result']['content'][0]['text']);
    }

    public function testUnknownMethodReturnsError(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'unknown/method',
        ]);

        $this->assertSame(7, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame(-32_601, $response['error']['code']);
    }

    public function testNotificationReturnsNull(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'method' => 'initialized',
        ]);

        $this->assertNull($response);
    }

    public function testMissingMethodWithIdReturnsInvalidRequest(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
            'id' => 8,
        ]);

        $this->assertSame(8, $response['id']);
        $this->assertSame(-32_600, $response['error']['code']);
    }

    public function testMissingMethodWithoutIdReturnsNull(): void
    {
        $server = $this->createServer();

        $response = $server->process([
            'jsonrpc' => '2.0',
        ]);

        $this->assertNull($response);
    }

    private function createServer(?ToolRegistry $registry = null): McpServer
    {
        return new McpServer($registry ?? new ToolRegistry());
    }
}
