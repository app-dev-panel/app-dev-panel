#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Modulite boundary checker — validates that modules only import from declared dependencies.
 *
 * Inspired by VK's Modulite (https://github.com/VKCOM/modulite).
 * Scans PHP `use` statements in each module and reports violations where a module
 * imports from another internal module not listed in its `requires`.
 *
 * Usage:
 *   php tools/modulite-check.php                  # default output
 *   php tools/modulite-check.php --format=github  # GitHub Actions annotation format
 *   php tools/modulite-check.php --format=json    # JSON output
 *
 * Exit codes:
 *   0 — no violations
 *   1 — violations found
 *   2 — configuration error
 */

$rootDir = dirname(__DIR__);
$configFile = $rootDir . '/modulite.php';

if (!file_exists($configFile)) {
    fwrite(STDERR, "Error: modulite.php not found in project root.\n");
    exit(2);
}

$modules = require $configFile;
$format = 'default';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
}

if (!in_array($format, ['default', 'github', 'json'], true)) {
    fwrite(STDERR, "Error: unknown format '{$format}'. Use: default, github, json\n");
    exit(2);
}

// Build namespace-to-module lookup table
$namespaceMap = [];
foreach ($modules as $id => $module) {
    $namespaceMap[$module['namespace']] = $id;
}

// Sort by namespace length descending for longest-prefix matching
uksort($namespaceMap, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

/**
 * Resolve a fully-qualified class name to its module ID, or null if external.
 */
function resolveModule(string $fqcn, array $namespaceMap): ?string
{
    foreach ($namespaceMap as $ns => $moduleId) {
        if (str_starts_with($fqcn, $ns)) {
            return $moduleId;
        }
    }
    return null;
}

/**
 * Extract `use` statements from a PHP file.
 * Returns array of [fqcn, lineNumber] pairs.
 */
function extractUseStatements(string $filePath): array
{
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        return [];
    }

    $uses = [];
    $lines = explode("\n", $contents);

    foreach ($lines as $lineIdx => $line) {
        $trimmed = trim($line);

        // Skip `use` inside class bodies (trait imports, closures)
        // We only want top-level `use` statements (after namespace, before class)
        if (preg_match('/^use\s+(?!function\s|const\s)([A-Z][A-Za-z0-9_\\\\]+)/', $trimmed, $m)) {
            $uses[] = [$m[1], $lineIdx + 1];
        }

        // Handle grouped use: `use AppDevPanel\Kernel\{Foo, Bar};`
        if (preg_match('/^use\s+([A-Z][A-Za-z0-9_\\\\]+)\\\\\{(.+)\}/', $trimmed, $m)) {
            $prefix = $m[1] . '\\';
            $items = array_map('trim', explode(',', $m[2]));
            foreach ($items as $item) {
                // Remove `as Alias` if present
                $item = preg_replace('/\s+as\s+\w+/', '', $item);
                if ($item !== '' && ctype_upper($item[0])) {
                    $uses[] = [$prefix . $item, $lineIdx + 1];
                }
            }
        }
    }

    return $uses;
}

/**
 * Recursively find all .php files in a directory.
 */
function findPhpFiles(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $dir,
        RecursiveDirectoryIterator::SKIP_DOTS,
    ));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);
    return $files;
}

// ============================================================================
// Main validation loop
// ============================================================================

$violations = [];
$stats = [
    'modules' => count($modules),
    'files_scanned' => 0,
    'use_statements' => 0,
    'internal_refs' => 0,
    'violations' => 0,
];

foreach ($modules as $moduleId => $module) {
    $srcDir = $rootDir . '/' . $module['path'];
    $allowedModules = $module['requires'];
    $files = findPhpFiles($srcDir);

    foreach ($files as $filePath) {
        $stats['files_scanned']++;
        $useStatements = extractUseStatements($filePath);

        foreach ($useStatements as [$fqcn, $line]) {
            $stats['use_statements']++;

            // Skip self-references
            if (str_starts_with($fqcn, $module['namespace'])) {
                continue;
            }

            $targetModule = resolveModule($fqcn, $namespaceMap);

            // Skip external (vendor) namespaces
            if ($targetModule === null) {
                continue;
            }

            $stats['internal_refs']++;

            // Check if this dependency is allowed
            if (!in_array($targetModule, $allowedModules, true)) {
                $relativePath = str_replace($rootDir . '/', '', $filePath);
                $violations[] = [
                    'module' => $moduleId,
                    'file' => $relativePath,
                    'line' => $line,
                    'fqcn' => $fqcn,
                    'target_module' => $targetModule,
                    'allowed' => $allowedModules,
                ];
                $stats['violations']++;
            }
        }
    }
}

// ============================================================================
// Output
// ============================================================================

if ($format === 'json') {
    echo
        json_encode([
            'stats' => $stats,
            'violations' => $violations,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    ;
    exit($violations !== [] ? 1 : 0);
}

if ($format === 'github') {
    foreach ($violations as $v) {
        echo
            sprintf(
                "::error file=%s,line=%d::Modulite violation: module '%s' imports '%s' from module '%s' (not in requires: [%s])\n",
                $v['file'],
                $v['line'],
                $v['module'],
                $v['fqcn'],
                $v['target_module'],
                implode(', ', $v['allowed']),
            )
        ;
    }

    if ($violations === []) {
        echo
            "Modulite check passed: {$stats['files_scanned']} files, {$stats['internal_refs']} internal refs, 0 violations.\n"
        ;
    }

    exit($violations !== [] ? 1 : 0);
}

// Default format
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$cyan = "\033[0;36m";
$dim = "\033[2m";
$reset = "\033[0m";
$bold = "\033[1m";

echo "\n{$cyan}{$bold}Modulite Boundary Check{$reset}\n";
echo "{$dim}Inspired by VK Modulite — https://github.com/VKCOM/modulite{$reset}\n\n";

if ($violations !== []) {
    echo "{$red}{$bold}VIOLATIONS FOUND:{$reset}\n\n";

    $grouped = [];
    foreach ($violations as $v) {
        $grouped[$v['module']][] = $v;
    }

    foreach ($grouped as $moduleId => $moduleViolations) {
        echo
            "  {$yellow}{$bold}@{$moduleId}{$reset} "
                . "{$dim}(requires: ["
                . implode(', ', $modules[$moduleId]['requires'])
                . "]){$reset}\n"
        ;

        foreach ($moduleViolations as $v) {
            echo "    {$red}x{$reset} {$v['file']}:{$v['line']}\n";
            echo "      imports {$bold}{$v['fqcn']}{$reset}\n";
            echo "      from module {$yellow}@{$v['target_module']}{$reset} {$dim}(not declared){$reset}\n\n";
        }
    }
}

echo "{$dim}---{$reset}\n";
echo "  Modules:        {$stats['modules']}\n";
echo "  Files scanned:  {$stats['files_scanned']}\n";
echo "  Use statements: {$stats['use_statements']}\n";
echo "  Internal refs:  {$stats['internal_refs']}\n";
echo '  Violations:     ';

if ($stats['violations'] > 0) {
    echo "{$red}{$bold}{$stats['violations']}{$reset}\n";
} else {
    echo "{$green}{$bold}0{$reset}\n";
}

echo "\n";

if ($violations === []) {
    echo "{$green}{$bold}All module boundaries are respected.{$reset}\n\n";
} else {
    echo "{$red}Fix violations by either:{$reset}\n";
    echo "  1. Adding the missing module to 'requires' in modulite.php\n";
    echo "  2. Removing the unauthorized import\n";
    echo "  3. Moving shared code to a common dependency\n\n";
}

exit($violations !== [] ? 1 : 0);
