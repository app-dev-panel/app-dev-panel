<?php

declare(strict_types=1);

/**
 * Modulite — Module boundary and dependency rules for ADP libs.
 *
 * Inspired by VK's Modulite system (https://github.com/VKCOM/modulite).
 * Defines allowed dependencies between internal modules. Each module declares:
 *   - namespace: PSR-4 root namespace
 *   - path: source directory (relative to project root)
 *   - requires: list of other module IDs this module may depend on
 *
 * The validation script (tools/modulite-check.php) scans `use` statements
 * in each module's source files and verifies they only reference:
 *   1. Classes within the same module's namespace
 *   2. Classes from explicitly declared required modules
 *   3. External (vendor) namespaces (not governed by these rules)
 *
 * Dependency graph:
 *
 *   Kernel  (foundation, no internal deps)
 *     ^
 *     |
 *   McpServer --> Kernel
 *     ^
 *     |
 *   API ---------> Kernel, McpServer
 *     ^
 *     |
 *   Cli ---------> Kernel, API, McpServer
 *
 *   Testing  (independent, no internal deps)
 *
 *   Adapter/Yii3 -----> Kernel, API, Cli, McpServer
 *   Adapter/Symfony ---> Kernel, API, Cli, McpServer
 *   Adapter/Laravel ---> Kernel, API, Cli, McpServer
 *   Adapter/Yii2 -----> Kernel, API, Cli, McpServer
 *   Adapter/Spiral ---> Kernel, API, Cli, McpServer
 *   Adapter/Cycle -----> API
 */
return [
    // =========================================================================
    // Core modules (framework-independent)
    // =========================================================================

    'kernel' => [
        'namespace' => 'AppDevPanel\\Kernel\\',
        'path' => 'libs/Kernel/src',
        'requires' => [],
    ],

    'mcp-server' => [
        'namespace' => 'AppDevPanel\\McpServer\\',
        'path' => 'libs/McpServer/src',
        'requires' => ['kernel'],
    ],

    'api' => [
        'namespace' => 'AppDevPanel\\Api\\',
        'path' => 'libs/API/src',
        'requires' => ['kernel', 'mcp-server'],
    ],

    'cli' => [
        'namespace' => 'AppDevPanel\\Cli\\',
        'path' => 'libs/Cli/src',
        'requires' => ['kernel', 'api', 'mcp-server'],
    ],

    'testing' => [
        'namespace' => 'AppDevPanel\\Testing\\',
        'path' => 'libs/Testing/src',
        'requires' => [],
    ],

    // =========================================================================
    // Framework adapters
    // =========================================================================

    'adapter-yii3' => [
        'namespace' => 'AppDevPanel\\Adapter\\Yii3\\',
        'path' => ['libs/Adapter/Yii3/src', 'libs/Adapter/Yii3/config'],
        'requires' => ['kernel', 'api', 'cli', 'mcp-server'],
    ],

    'adapter-symfony' => [
        'namespace' => 'AppDevPanel\\Adapter\\Symfony\\',
        'path' => 'libs/Adapter/Symfony/src',
        'requires' => ['kernel', 'api', 'cli', 'mcp-server'],
    ],

    'adapter-laravel' => [
        'namespace' => 'AppDevPanel\\Adapter\\Laravel\\',
        'path' => 'libs/Adapter/Laravel/src',
        'requires' => ['kernel', 'api', 'cli', 'mcp-server'],
    ],

    'adapter-yii2' => [
        'namespace' => 'AppDevPanel\\Adapter\\Yii2\\',
        'path' => 'libs/Adapter/Yii2/src',
        'requires' => ['kernel', 'api', 'cli', 'mcp-server'],
    ],

    'adapter-spiral' => [
        'namespace' => 'AppDevPanel\\Adapter\\Spiral\\',
        'path' => 'libs/Adapter/Spiral/src',
        'requires' => ['kernel', 'api', 'cli', 'mcp-server'],
    ],

    'adapter-cycle' => [
        'namespace' => 'AppDevPanel\\Adapter\\Cycle\\',
        'path' => 'libs/Adapter/Cycle/src',
        'requires' => ['api'],
    ],
];
