<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Mcp\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Mcp\McpSettings;
use AppDevPanel\McpServer\McpServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP controller for MCP (Model Context Protocol) over Streamable HTTP transport.
 *
 * Accepts JSON-RPC 2.0 requests via POST and returns JSON-RPC responses.
 * This enables AI assistants to use ADP tools over HTTP when the ADP server is running.
 */
final class McpController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly McpServer $mcpServer,
        private readonly ?McpSettings $mcpSettings = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->mcpSettings !== null && !$this->mcpSettings->isEnabled()) {
            return $this->jsonRpcError(null, -32_000, 'MCP server is disabled');
        }

        $body = (string) $request->getBody();

        if ($body === '') {
            return $this->jsonRpcError(null, -32_700, 'Parse error: empty request body');
        }

        $message = json_decode($body, true, 512);

        if (!is_array($message)) {
            return $this->jsonRpcError(null, -32_700, 'Parse error: invalid JSON');
        }

        $response = $this->mcpServer->process($message);

        if ($response === null) {
            // Notification — return 204 No Content
            return $this->responseFactory->createJsonResponse(null, 204);
        }

        return $this->responseFactory->createJsonResponse($response);
    }

    private function jsonRpcError(int|string|null $id, int $code, string $message): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 400);
    }
}
