<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit;

use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use AppDevPanel\McpServer\Transport\StdioTransport;
use PHPUnit\Framework\TestCase;

final class McpServerTest extends TestCase
{
    public function testInitializeReturnsCapabilities(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ]);

        $response = $this->readResponse($output);

        $this->assertSame(1, $response['id']);
        $this->assertSame('2024-11-05', $response['result']['protocolVersion']);
        $this->assertSame('adp-mcp', $response['result']['serverInfo']['name']);
        $this->assertArrayHasKey('tools', $response['result']['capabilities']);
    }

    public function testPingReturnsEmptyResult(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'ping',
        ]);

        $response = $this->readResponse($output);

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

        [$server, $output] = $this->createServer($registry);

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/list',
        ]);

        $response = $this->readResponse($output);

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

        [$server, $output] = $this->createServer($registry);

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => ['name' => 'test_tool', 'arguments' => ['arg1' => 'value1']],
        ]);

        $response = $this->readResponse($output);

        $this->assertSame(4, $response['id']);
        $this->assertSame('result', $response['result']['content'][0]['text']);
    }

    public function testToolsCallWithUnknownToolReturnsError(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => ['name' => 'nonexistent'],
        ]);

        $response = $this->readResponse($output);

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

        [$server, $output] = $this->createServer($registry);

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'tools/call',
            'params' => ['name' => 'failing_tool', 'arguments' => []],
        ]);

        $response = $this->readResponse($output);

        $this->assertSame(6, $response['id']);
        $this->assertTrue($response['result']['isError']);
        $this->assertStringContainsString('Something broke', $response['result']['content'][0]['text']);
    }

    public function testUnknownMethodReturnsError(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'unknown/method',
        ]);

        $response = $this->readResponse($output);

        $this->assertSame(7, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame(-32_601, $response['error']['code']);
    }

    public function testNotificationDoesNotProduceResponse(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'method' => 'initialized',
        ]);

        rewind($output);
        $content = stream_get_contents($output);

        $this->assertSame('', $content);
    }

    public function testMissingMethodWithIdReturnsInvalidRequest(): void
    {
        [$server, $output] = $this->createServer();

        $server->handleMessage([
            'jsonrpc' => '2.0',
            'id' => 8,
        ]);

        $response = $this->readResponse($output);

        $this->assertSame(8, $response['id']);
        $this->assertSame(-32_600, $response['error']['code']);
    }

    /**
     * @return array{McpServer, resource}
     */
    private function createServer(?ToolRegistry $registry = null): array
    {
        $output = fopen('php://memory', 'rw');
        $transport = new StdioTransport(STDIN, $output);

        return [new McpServer($transport, $registry ?? new ToolRegistry()), $output];
    }

    private function readResponse(mixed $output): array
    {
        rewind($output);
        $line = fgets($output);

        return json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    }
}
