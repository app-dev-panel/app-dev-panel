<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

/**
 * Configuration for the MCP tool registry.
 * Controls which optional inspector tools are registered when an InspectorInterface is provided.
 */
final class McpConfig
{
    /**
     * @param list<string>|null $allowedInspectorTools Tool names to register.
     *                                                  null  = all inspector tools (default)
     *                                                  []    = none
     *                                                  ['inspect_routes'] = specific tools only
     */
    public function __construct(
        public readonly ?array $allowedInspectorTools = null,
    ) {}
}
