<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Mcp\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Mcp\McpSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exposes MCP server settings (enabled/disabled) to the frontend.
 */
final class McpSettingsController
{
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly McpSettings $mcpSettings,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'enabled' => $this->mcpSettings->isEnabled(),
        ]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body) || !array_key_exists('enabled', $body)) {
            return $this->responseFactory->createJsonResponse(['error' => 'Missing "enabled" field'], 400);
        }

        $this->mcpSettings->setEnabled((bool) $body['enabled']);

        return $this->responseFactory->createJsonResponse([
            'enabled' => $this->mcpSettings->isEnabled(),
        ]);
    }
}
