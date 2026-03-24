<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

use AppDevPanel\McpServer\Tool\ToolRegistry;
use AppDevPanel\McpServer\Transport\StdioTransport;

/**
 * MCP (Model Context Protocol) server for ADP.
 *
 * Implements the MCP protocol over stdio transport (JSON-RPC 2.0).
 * Exposes debug data tools to AI assistants.
 */
final class McpServer
{
    private const string SERVER_NAME = 'adp-mcp';
    private const string SERVER_VERSION = '1.0.0';
    private const string PROTOCOL_VERSION = '2024-11-05';

    private bool $initialized = false;

    public function __construct(
        private readonly StdioTransport $transport,
        private readonly ToolRegistry $toolRegistry,
    ) {}

    /**
     * Run the MCP server main loop. Blocks until stdin is closed.
     */
    public function run(): void
    {
        while (true) {
            $message = $this->transport->receive();

            if ($message === null) {
                break;
            }

            $this->handleMessage($message);
        }
    }

    /**
     * Handle a single incoming JSON-RPC message. Useful for testing.
     */
    public function handleMessage(array $message): void
    {
        $method = $message['method'] ?? null;
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];

        if ($method === null) {
            if ($id !== null) {
                $this->sendError($id, -32_600, 'Invalid Request: missing method');
            }
            return;
        }

        // Notifications (no id) — no response expected
        if ($id === null) {
            $this->handleNotification($method, $params);
            return;
        }

        // Requests (with id) — response expected
        $this->handleRequest($id, $method, $params);
    }

    private function handleNotification(string $method, array $params): void
    {
        match ($method) {
            'initialized' => $this->initialized = true,
            'notifications/cancelled' => null,
            default => null,
        };
    }

    private function handleRequest(int|string $id, string $method, array $params): void
    {
        match ($method) {
            'initialize' => $this->handleInitialize($id, $params),
            'ping' => $this->sendResult($id, []),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $params),
            default => $this->sendError($id, -32_601, sprintf('Method not found: %s', $method)),
        };
    }

    private function handleInitialize(int|string $id, array $params): void
    {
        $this->sendResult($id, [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
        ]);
    }

    private function handleToolsList(int|string $id): void
    {
        $this->sendResult($id, [
            'tools' => $this->toolRegistry->list(),
        ]);
    }

    private function handleToolsCall(int|string $id, array $params): void
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $tool = $this->toolRegistry->get($toolName);

        if ($tool === null) {
            $this->sendResult($id, [
                'content' => [['type' => 'text', 'text' => sprintf('Unknown tool: %s', $toolName)]],
                'isError' => true,
            ]);
            return;
        }

        try {
            $result = $tool->execute($arguments);
            $this->sendResult($id, $result);
        } catch (\Throwable $e) {
            $this->sendResult($id, [
                'content' => [['type' => 'text', 'text' => sprintf('Tool error: %s', $e->getMessage())]],
                'isError' => true,
            ]);
        }
    }

    private function sendResult(int|string $id, mixed $result): void
    {
        $this->transport->send([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    private function sendError(int|string $id, int $code, string $message): void
    {
        $this->transport->send([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }
}
