<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\TaskBus\Transport\JsonRpcHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP controller exposing TaskBus JSON-RPC 2.0 over the inspector API.
 *
 * POST /inspect/api/taskbus — JSON-RPC endpoint (task.submit, task.list, schedule.*, etc.)
 * GET  /inspect/api/taskbus/status — health check / summary
 */
final class TaskBusController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly JsonRpcHandler $rpcHandler,
    ) {}

    /**
     * Handle JSON-RPC 2.0 request (same protocol as MCP endpoint).
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();

        if ($body === '') {
            return $this->jsonRpcError(null, -32_700, 'Parse error: empty request body');
        }

        $response = $this->rpcHandler->handle($body);

        if ($response === null) {
            // JSON-RPC notification — no response expected
            return $this->responseFactory->createJsonResponse(null, 204);
        }

        // Return raw JSON-RPC response (bypass ResponseDataWrapper)
        return $this->responseFactory->createJsonResponse(json_decode($response, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Health check / summary endpoint.
     */
    public function status(): ResponseInterface
    {
        $summaryRequest = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'task.list',
            'params' => ['limit' => 0],
            'id' => 'health',
        ]);

        $response = $this->rpcHandler->handle($summaryRequest);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $this->responseFactory->createJsonResponse([
            'active' => true,
            'total' => $data['result']['total'] ?? 0,
        ]);
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
