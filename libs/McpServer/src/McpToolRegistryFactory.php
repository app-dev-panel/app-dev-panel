<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\Debug\AnalyzeExceptionTool;
use AppDevPanel\McpServer\Tool\Debug\ListEntriesTool;
use AppDevPanel\McpServer\Tool\Debug\SearchLogsTool;
use AppDevPanel\McpServer\Tool\Debug\ViewDatabaseQueriesTool;
use AppDevPanel\McpServer\Tool\Debug\ViewEntryTool;
use AppDevPanel\McpServer\Tool\Debug\ViewTimelineTool;
use AppDevPanel\McpServer\Tool\Inspector\InspectConfigTool;
use AppDevPanel\McpServer\Tool\Inspector\InspectDatabaseSchemaTool;
use AppDevPanel\McpServer\Tool\Inspector\InspectRoutesTool;
use AppDevPanel\McpServer\Tool\ToolRegistry;

/**
 * Creates a ToolRegistry with all debug and inspector tools pre-registered.
 * Shared between stdio (bin/adp-mcp, mcp:serve) and HTTP (McpController) entry points.
 */
final class McpToolRegistryFactory
{
    public const string TOOL_INSPECT_CONFIG = 'inspect_config';
    public const string TOOL_INSPECT_ROUTES = 'inspect_routes';
    public const string TOOL_INSPECT_SCHEMA = 'inspect_database_schema';

    /**
     * Build a ToolRegistry populated with debug tools and, optionally, inspector tools.
     *
     * @param InspectorInterface|null $inspectorClient When provided, inspector tools are registered.
     * @param McpConfig|null          $config          Controls which inspector tools to include.
     *                                                 null = use defaults (all inspector tools when client is set).
     */
    public static function create(
        StorageInterface $storage,
        ?InspectorInterface $inspectorClient = null,
        ?McpConfig $config = null,
    ): ToolRegistry {
        $registry = new ToolRegistry();

        // Debug tools (read from storage)
        $registry->register(new ListEntriesTool($storage));
        $registry->register(new ViewEntryTool($storage));
        $registry->register(new SearchLogsTool($storage));
        $registry->register(new AnalyzeExceptionTool($storage));
        $registry->register(new ViewDatabaseQueriesTool($storage));
        $registry->register(new ViewTimelineTool($storage));

        // Inspector tools (query live app via HTTP) — only registered when a client is provided
        if ($inspectorClient !== null) {
            $allowed = $config?->allowedInspectorTools;

            if ($allowed === null || in_array(self::TOOL_INSPECT_CONFIG, $allowed, true)) {
                $registry->register(new InspectConfigTool($inspectorClient));
            }

            if ($allowed === null || in_array(self::TOOL_INSPECT_ROUTES, $allowed, true)) {
                $registry->register(new InspectRoutesTool($inspectorClient));
            }

            if ($allowed === null || in_array(self::TOOL_INSPECT_SCHEMA, $allowed, true)) {
                $registry->register(new InspectDatabaseSchemaTool($inspectorClient));
            }
        }

        return $registry;
    }
}
