<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests that verify the MCP (Model Context Protocol) API endpoint works correctly.
 * Tests JSON-RPC 2.0 protocol over HTTP transport at POST /debug/api/mcp.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group mcp
 */
#[Group('fixtures')]
#[Group('mcp')]
final class McpApiTest extends TestCase
{
    private static Client $client;
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim((string) getenv('PLAYGROUND_URL') ?: 'http://127.0.0.1:8080', '/');
        self::$client = new Client([
            'base_uri' => self::$baseUrl,
            'http_errors' => false,
            'timeout' => 10,
        ]);

        // Verify server reachable
        try {
            $response = self::$client->get('/');
            if ($response->getStatusCode() === 0) {
                self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
        }

        // Generate debug data by hitting a fixture endpoint
        self::$client->get('/test/fixtures/logs');
        usleep(500_000);
    }

    public function testInitializeReturnsServerCapabilities(): void
    {
        $response = self::jsonRpc('initialize', ['protocolVersion' => '2024-11-05'], 1);

        self::assertSame(200, $response['status']);
        self::assertSame('2.0', $response['body']['jsonrpc']);
        self::assertSame(1, $response['body']['id']);
        self::assertArrayHasKey('result', $response['body']);

        $result = $response['body']['result'];
        self::assertSame('2024-11-05', $result['protocolVersion']);
        self::assertArrayHasKey('capabilities', $result);
        self::assertArrayHasKey('tools', $result['capabilities']);
        self::assertArrayHasKey('serverInfo', $result);
        self::assertSame('adp-mcp', $result['serverInfo']['name']);
    }

    public function testInitializedNotificationReturns204(): void
    {
        $response = self::$client->post('/debug/api/mcp', [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => 'initialized',
            ],
        ]);

        self::assertSame(204, $response->getStatusCode());
    }

    public function testPingReturnsEmptyResult(): void
    {
        $response = self::jsonRpc('ping', [], 2);

        self::assertSame(200, $response['status']);
        self::assertSame(2, $response['body']['id']);
        self::assertArrayHasKey('result', $response['body']);
    }

    public function testToolsListReturnsAllTools(): void
    {
        $response = self::jsonRpc('tools/list', [], 3);

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('result', $response['body']);
        self::assertArrayHasKey('tools', $response['body']['result']);

        $tools = $response['body']['result']['tools'];
        self::assertIsArray($tools);
        self::assertNotEmpty($tools, 'MCP server should expose at least one tool');

        $toolNames = array_column($tools, 'name');
        self::assertContains('list_debug_entries', $toolNames);
        self::assertContains('view_debug_entry', $toolNames);
        self::assertContains('search_logs', $toolNames);
        self::assertContains('analyze_exception', $toolNames);
        self::assertContains('view_database_queries', $toolNames);
        self::assertContains('view_timeline', $toolNames);

        // Each tool should have name, description, and inputSchema
        foreach ($tools as $tool) {
            self::assertArrayHasKey('name', $tool);
            self::assertArrayHasKey('description', $tool);
            self::assertArrayHasKey('inputSchema', $tool);
            self::assertIsArray($tool['inputSchema']);
        }
    }

    public function testToolsCallListDebugEntries(): void
    {
        $response = self::jsonRpc(
            'tools/call',
            [
                'name' => 'list_debug_entries',
                'arguments' => ['limit' => 5],
            ],
            4,
        );

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('result', $response['body']);

        $result = $response['body']['result'];
        self::assertArrayHasKey('content', $result);
        self::assertIsArray($result['content']);
        self::assertNotEmpty($result['content']);
        self::assertSame('text', $result['content'][0]['type']);
        self::assertNotEmpty($result['content'][0]['text']);

        // Should not be an error
        self::assertFalse($result['isError'] ?? false);
    }

    public function testToolsCallViewDebugEntry(): void
    {
        // First get a debug entry ID
        $listResponse = self::$client->get('/debug/api/');
        $listBody = json_decode((string) $listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $listBody['data'] ?? $listBody;

        self::assertNotEmpty($entries, 'Should have at least one debug entry');
        $entryId = reset($entries)['id'] ?? null;
        self::assertNotNull($entryId);

        $response = self::jsonRpc(
            'tools/call',
            [
                'name' => 'view_debug_entry',
                'arguments' => ['id' => $entryId],
            ],
            5,
        );

        self::assertSame(200, $response['status']);

        $result = $response['body']['result'];
        self::assertArrayHasKey('content', $result);
        self::assertNotEmpty($result['content']);
        self::assertSame('text', $result['content'][0]['type']);
        self::assertFalse($result['isError'] ?? false);
    }

    public function testToolsCallSearchLogs(): void
    {
        $response = self::jsonRpc(
            'tools/call',
            [
                'name' => 'search_logs',
                'arguments' => ['query' => 'test', 'limit' => 10],
            ],
            6,
        );

        self::assertSame(200, $response['status']);

        $result = $response['body']['result'];
        self::assertArrayHasKey('content', $result);
        self::assertNotEmpty($result['content']);
        self::assertSame('text', $result['content'][0]['type']);
        self::assertFalse($result['isError'] ?? false);
    }

    public function testToolsCallViewTimeline(): void
    {
        // Get a debug entry ID
        $listResponse = self::$client->get('/debug/api/');
        $listBody = json_decode((string) $listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $listBody['data'] ?? $listBody;
        $entryId = reset($entries)['id'] ?? null;

        self::assertNotNull($entryId);

        $response = self::jsonRpc(
            'tools/call',
            [
                'name' => 'view_timeline',
                'arguments' => ['id' => $entryId],
            ],
            7,
        );

        self::assertSame(200, $response['status']);

        $result = $response['body']['result'];
        self::assertArrayHasKey('content', $result);
        self::assertNotEmpty($result['content']);
        self::assertSame('text', $result['content'][0]['type']);
    }

    public function testToolsCallWithUnknownToolReturnsError(): void
    {
        $response = self::jsonRpc(
            'tools/call',
            [
                'name' => 'nonexistent_tool',
                'arguments' => [],
            ],
            8,
        );

        self::assertSame(200, $response['status']);

        $result = $response['body']['result'];
        self::assertTrue($result['isError'] ?? false, 'Unknown tool should return isError=true');
    }

    public function testUnknownMethodReturnsMethodNotFoundError(): void
    {
        $response = self::jsonRpc('nonexistent/method', [], 9);

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('error', $response['body']);
        self::assertSame(-32_601, $response['body']['error']['code']);
    }

    public function testEmptyBodyReturns400(): void
    {
        $response = self::$client->post('/debug/api/mcp', [
            'body' => '',
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        self::assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $body);
        self::assertSame(-32_700, $body['error']['code']);
    }

    public function testInvalidJsonReturns400(): void
    {
        $response = self::$client->post('/debug/api/mcp', [
            'body' => '{invalid json',
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        self::assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $body);
        self::assertSame(-32_700, $body['error']['code']);
    }

    public function testStringIdPreserved(): void
    {
        $response = self::jsonRpc('ping', [], 'my-string-id');

        self::assertSame(200, $response['status']);
        self::assertSame('my-string-id', $response['body']['id']);
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function jsonRpc(string $method, array $params, int|string $id): array
    {
        $response = self::$client->post('/debug/api/mcp', [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => $method,
                'params' => $params,
            ],
        ]);

        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'body' => $body];
    }
}
