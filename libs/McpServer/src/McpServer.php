<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

use AppDevPanel\McpServer\Tool\ToolRegistry;
use AppDevPanel\McpServer\Transport\StdioTransport;

/**
 * MCP (Model Context Protocol) server for ADP.
 *
 * Implements the MCP protocol (JSON-RPC 2.0).
 * Can run over stdio transport (for CLI) or be called directly via process() for HTTP.
 */
final class McpServer
{
    private const string SERVER_NAME = 'adp-mcp';
    private const string SERVER_VERSION = '1.0.0';
    private const string PROTOCOL_VERSION = '2024-11-05';

    private bool $initialized = false;

    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly ?StdioTransport $transport = null,
    ) {}

    /**
     * Run the MCP server main loop over stdio. Blocks until stdin is closed.
     */
    public function run(): void
    {
        if ($this->transport === null) {
            throw new \RuntimeException('Cannot run() without a stdio transport. Use process() for HTTP.');
        }

        while (true) {
            $message = $this->transport->receive();

            if ($message === null) {
                break;
            }

            $response = $this->process($message);

            if ($response !== null) {
                $this->transport->send($response);
            }
        }
    }

    /**
     * Process a single JSON-RPC message and return the response (or null for notifications).
     *
     * @return array<string, mixed>|null JSON-RPC response, or null for notifications
     */
    public function process(array $message): ?array
    {
        $method = $message['method'] ?? null;
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];

        if ($method === null) {
            if ($id !== null) {
                return self::errorResponse($id, -32_600, 'Invalid Request: missing method');
            }
            return null;
        }

        // Notifications (no id) — no response
        if ($id === null) {
            $this->handleNotification($method, $params);
            return null;
        }

        // Requests (with id) — return response
        return $this->handleRequest($id, $method, $params);
    }

    private function handleNotification(string $method, array $params): void
    {
        match ($method) {
            'initialized' => $this->initialized = true,
            'notifications/cancelled' => null,
            default => null,
        };
    }

    private function handleRequest(int|string $id, string $method, array $params): array
    {
        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'ping' => self::resultResponse($id, []),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $params),
            default => self::errorResponse($id, -32_601, sprintf('Method not found: %s', $method)),
        };
    }

    private function handleInitialize(int|string $id): array
    {
        return self::resultResponse($id, [
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

    private function handleToolsList(int|string $id): array
    {
        return self::resultResponse($id, [
            'tools' => $this->toolRegistry->list(),
        ]);
    }

    private function handleToolsCall(int|string $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $tool = $this->toolRegistry->get($toolName);

        if ($tool === null) {
            return self::resultResponse($id, [
                'content' => [['type' => 'text', 'text' => sprintf('Unknown tool: %s', $toolName)]],
                'isError' => true,
            ]);
        }

        try {
            $result = $tool->execute($arguments);
            return self::resultResponse($id, $result);
        } catch (\Throwable $e) {
            return self::resultResponse($id, [
                'content' => [['type' => 'text', 'text' => sprintf('Tool error: %s', $e->getMessage())]],
                'isError' => true,
            ]);
        }
    }

    /**
     * @return array{jsonrpc: string, id: int|string, result: mixed}
     */
    private static function resultResponse(int|string $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * @return array{jsonrpc: string, id: int|string, error: array{code: int, message: string}}
     */
    private static function errorResponse(int|string $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
