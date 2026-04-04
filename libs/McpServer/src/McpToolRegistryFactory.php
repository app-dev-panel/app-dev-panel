<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Inspector\InspectorClient;
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
    public static function create(StorageInterface $storage, ?InspectorClient $inspectorClient = null): ToolRegistry
    {
        $registry = new ToolRegistry();

        // Debug tools (read from storage)
        $registry->register(new ListEntriesTool($storage));
        $registry->register(new ViewEntryTool($storage));
        $registry->register(new SearchLogsTool($storage));
        $registry->register(new AnalyzeExceptionTool($storage));
        $registry->register(new ViewDatabaseQueriesTool($storage));
        $registry->register(new ViewTimelineTool($storage));

        // Inspector tools (query live app via HTTP)
        if ($inspectorClient !== null) {
            $registry->register(new InspectConfigTool($inspectorClient));
            $registry->register(new InspectRoutesTool($inspectorClient));
            $registry->register(new InspectDatabaseSchemaTool($inspectorClient));
        }

        return $registry;
    }
}
