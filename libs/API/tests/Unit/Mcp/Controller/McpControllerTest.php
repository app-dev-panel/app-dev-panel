<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Mcp\Controller;

use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\Tool\ToolRegistry;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class McpControllerTest extends TestCase
{
    public function testHandleInitialize(): void
    {
        $controller = $this->createController();

        $request = $this->createJsonRequest([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2024-11-05'],
        ]);

        $response = $controller->handle($request);

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2.0', $body['jsonrpc']);
        $this->assertSame(1, $body['id']);
        $this->assertSame('adp-mcp', $body['result']['serverInfo']['name']);
    }

    public function testHandlePing(): void
    {
        $controller = $this->createController();

        $request = $this->createJsonRequest([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'ping',
        ]);

        $response = $controller->handle($request);

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(2, $body['id']);
        $this->assertSame([], $body['result']);
    }

    public function testHandleToolsList(): void
    {
        $controller = $this->createController();

        $request = $this->createJsonRequest([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/list',
        ]);

        $response = $controller->handle($request);

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('tools', $body['result']);
    }

    public function testHandleEmptyBody(): void
    {
        $controller = $this->createController();

        $request = new ServerRequest('POST', '/debug/api/mcp');

        $response = $controller->handle($request);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('error', $body);
        $this->assertSame(-32_700, $body['error']['code']);
    }

    public function testHandleInvalidJson(): void
    {
        $controller = $this->createController();

        $httpFactory = new HttpFactory();
        $request = new ServerRequest('POST', '/debug/api/mcp', [], $httpFactory->createStream('not-json'));

        $response = $controller->handle($request);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(-32_700, $body['error']['code']);
    }

    public function testHandleNotificationReturns204(): void
    {
        $controller = $this->createController();

        $request = $this->createJsonRequest([
            'method' => 'initialized',
        ]);

        $response = $controller->handle($request);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testHandleReturnsErrorWhenMcpDisabled(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-mcp-settings-' . uniqid();
        mkdir($tmpDir, 0o755, true);

        try {
            $mcpSettings = new \AppDevPanel\Api\Mcp\McpSettings($tmpDir);
            $mcpSettings->setEnabled(false);

            $httpFactory = new HttpFactory();
            $jsonResponseFactory = new JsonResponseFactory($httpFactory, $httpFactory);
            $mcpServer = new McpServer(new ToolRegistry());
            $controller = new McpController($jsonResponseFactory, $mcpServer, $mcpSettings);

            $request = $this->createJsonRequest([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'ping',
            ]);

            $response = $controller->handle($request);

            $this->assertSame(400, $response->getStatusCode());
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(-32_000, $body['error']['code']);
            $this->assertStringContainsString('disabled', $body['error']['message']);
        } finally {
            @unlink($tmpDir . '/mcp-settings.json');
            @rmdir($tmpDir);
        }
    }

    private function createController(): McpController
    {
        $httpFactory = new HttpFactory();
        $jsonResponseFactory = new JsonResponseFactory($httpFactory, $httpFactory);
        $mcpServer = new McpServer(new ToolRegistry());

        return new McpController($jsonResponseFactory, $mcpServer);
    }

    private function createJsonRequest(array $data): ServerRequest
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $httpFactory = new HttpFactory();

        return new ServerRequest(
            'POST',
            '/debug/api/mcp',
            ['Content-Type' => 'application/json'],
            $httpFactory->createStream($json),
        );
    }
}
