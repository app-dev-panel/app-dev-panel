<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer;

use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\Debug\AnalyzeExceptionTool;
use AppDevPanel\McpServer\Tool\Debug\ListEntriesTool;
use AppDevPanel\McpServer\Tool\Debug\SearchLogsTool;
use AppDevPanel\McpServer\Tool\Debug\ViewDatabaseQueriesTool;
use AppDevPanel\McpServer\Tool\Debug\ViewEntryTool;
use AppDevPanel\McpServer\Tool\Debug\ViewTimelineTool;
use AppDevPanel\McpServer\Tool\ToolRegistry;

/**
 * Creates a ToolRegistry with all debug tools pre-registered.
 * Shared between stdio (bin/adp-mcp, mcp:serve) and HTTP (McpController) entry points.
 */
final class McpToolRegistryFactory
{
    public static function create(StorageInterface $storage): ToolRegistry
    {
        $registry = new ToolRegistry();
        $registry->register(new ListEntriesTool($storage));
        $registry->register(new ViewEntryTool($storage));
        $registry->register(new SearchLogsTool($storage));
        $registry->register(new AnalyzeExceptionTool($storage));
        $registry->register(new ViewDatabaseQueriesTool($storage));
        $registry->register(new ViewTimelineTool($storage));

        return $registry;
    }
}
