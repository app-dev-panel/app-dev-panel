<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\Inspector\InspectorInterface;
use PHPUnit\Framework\TestCase;

final class InspectorClientTest extends TestCase
{
    // ── fromOptionalUrl ────────────────────────────────────────────────────

    public function testFromOptionalUrlReturnsNullForNull(): void
    {
        $this->assertNull(InspectorClient::fromOptionalUrl(null));
    }

    public function testFromOptionalUrlReturnsNullForEmptyString(): void
    {
        $this->assertNull(InspectorClient::fromOptionalUrl(''));
    }

    public function testFromOptionalUrlReturnsInstanceForValidUrl(): void
    {
        $client = InspectorClient::fromOptionalUrl('http://localhost:8080');

        $this->assertInstanceOf(InspectorClient::class, $client);
    }

    public function testFromOptionalUrlImplementsInterface(): void
    {
        $client = InspectorClient::fromOptionalUrl('http://localhost:8080');

        $this->assertInstanceOf(InspectorInterface::class, $client);
    }

    public function testFromOptionalUrlPreservesBaseUrl(): void
    {
        $client = InspectorClient::fromOptionalUrl('http://example.com:9000');

        $this->assertSame('http://example.com:9000', $client->getBaseUrl());
    }

    // ── getBaseUrl ─────────────────────────────────────────────────────────

    public function testGetBaseUrlReturnsConstructorValue(): void
    {
        $client = new InspectorClient('http://myapp.test');

        $this->assertSame('http://myapp.test', $client->getBaseUrl());
    }

    public function testGetBaseUrlWithTrailingSlash(): void
    {
        $client = new InspectorClient('http://myapp.test/');

        $this->assertSame('http://myapp.test/', $client->getBaseUrl());
    }

    // ── get() / post() — connection failure ────────────────────────────────

    public function testGetReturnsErrorOnConnectionFailure(): void
    {
        $client = new InspectorClient('http://127.0.0.1:1', timeoutSeconds: 1);

        $result = $client->get('/routes');

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertStringContainsString('127.0.0.1:1', $result['error']);
    }

    public function testPostReturnsErrorOnConnectionFailure(): void
    {
        $client = new InspectorClient('http://127.0.0.1:1', timeoutSeconds: 1);

        $result = $client->post('/params', ['key' => 'value']);

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertStringContainsString('127.0.0.1:1', $result['error']);
    }

    // ── response parsing ───────────────────────────────────────────────────

    /**
     * Tests the wrapped Inspector API format: {success: true, data: mixed, error: null}
     */
    public function testGetParsesWrappedSuccessResponse(): void
    {
        $client = $this->clientWithResponse(json_encode([
            'success' => true,
            'data' => ['routes' => ['/api', '/health']],
            'error' => null,
        ]));

        $result = $client->get('/routes');

        $this->assertTrue($result['success']);
        $this->assertSame(['routes' => ['/api', '/health']], $result['data']);
        $this->assertNull($result['error']);
    }

    public function testGetParsesWrappedFailureResponse(): void
    {
        $client = $this->clientWithResponse(json_encode([
            'success' => false,
            'data' => null,
            'error' => 'Not authorized',
        ]));

        $result = $client->get('/config');

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame('Not authorized', $result['error']);
    }

    public function testGetParsesWrappedFailureWithArrayError(): void
    {
        $client = $this->clientWithResponse(json_encode([
            'success' => false,
            'data' => null,
            'error' => ['message' => 'Server error', 'code' => 500],
        ]));

        $result = $client->get('/config');

        $this->assertFalse($result['success']);
        $this->assertSame('Server error', $result['error']);
    }

    public function testGetParsesRawResponse(): void
    {
        $client = $this->clientWithResponse(json_encode(['key' => 'value']));

        $result = $client->get('/raw');

        $this->assertTrue($result['success']);
        $this->assertSame(['key' => 'value'], $result['data']);
    }

    public function testGetReturnsErrorOnInvalidJson(): void
    {
        $client = $this->clientWithResponse('not valid json');

        $result = $client->get('/routes');

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid JSON response from Inspector API', $result['error']);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * Returns an InspectorClient that serves a fixed response body via a local PHP server.
     * The response is served once then the connection closes.
     */
    private function clientWithResponse(string $body): InspectorClient
    {
        // Start a minimal one-shot TCP server on a random port using a stream socket
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($server, "Could not start test server: $errstr");

        $name = stream_socket_get_name($server, false);
        [, $port] = explode(':', $name);

        // Serve the response in a child process to avoid blocking
        $pid = pcntl_fork();
        if ($pid === 0) {
            // Child: accept one connection, write HTTP response, exit
            $conn = @stream_socket_accept($server, 2.0);
            if ($conn !== false) {
                @fread($conn, 4096); // consume request
                $response = implode("\r\n", [
                    'HTTP/1.1 200 OK',
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($body),
                    'Connection: close',
                    '',
                    $body,
                ]);
                fwrite($conn, $response);
                fclose($conn);
            }
            fclose($server);
            exit(0);
        }

        fclose($server);

        return new InspectorClient("http://127.0.0.1:{$port}", timeoutSeconds: 2);
    }
}
