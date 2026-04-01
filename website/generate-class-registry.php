<?php

declare(strict_types=1);

/**
 * Generates class-registry.json for VitePress documentation.
 *
 * Scans all PHP source files in libs/, extracts:
 *   - FQCN (namespace + class name)
 *   - Type (class, interface, trait, enum)
 *   - PHPDoc first line (if any)
 *   - extends / implements
 *   - File path (relative to repo root)
 *   - GitHub source URL
 *
 * Usage: php generate-class-registry.php > .vitepress/class-registry.json
 */

$repoRoot = dirname(__DIR__);
$githubBase = 'https://github.com/app-dev-panel/app-dev-panel/blob/master';

$libraries = [
    'libs/Kernel/src' => 'AppDevPanel\\Kernel',
    'libs/API/src' => 'AppDevPanel\\Api',
    'libs/Cli/src' => 'AppDevPanel\\Cli',
    'libs/McpServer/src' => 'AppDevPanel\\McpServer',
    'libs/Testing/src' => 'AppDevPanel\\Testing',
    'libs/Adapter/Yii3/src' => 'AppDevPanel\\Adapter\\Yii3',
    'libs/Adapter/Symfony/src' => 'AppDevPanel\\Adapter\\Symfony',
    'libs/Adapter/Laravel/src' => 'AppDevPanel\\Adapter\\Laravel',
    'libs/Adapter/Yii2/src' => 'AppDevPanel\\Adapter\\Yii2',
    'libs/Adapter/Cycle/src' => 'AppDevPanel\\Adapter\\Cycle',
];

$registry = [];

foreach ($libraries as $srcDir => $expectedNamespace) {
    $fullDir = $repoRoot . '/' . $srcDir;
    if (!is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);

        $entry = parsePhpFile($content, $filePath, $repoRoot, $githubBase);
        if ($entry !== null) {
            $registry[$entry['fqcn']] = $entry;
        }
    }
}

// Sort by FQCN for stable output
ksort($registry);

echo json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

function parsePhpFile(string $content, string $filePath, string $repoRoot, string $githubBase): ?array
{
    // Extract namespace
    if (!preg_match('/namespace\s+([\w\\\\]+)\s*;/', $content, $nsMatch)) {
        return null;
    }
    $namespace = $nsMatch[1];

    // Extract class/interface/trait/enum declaration
    $declPattern = '/(?:(final|abstract|readonly)\s+)*'
        . '(class|interface|trait|enum)\s+'
        . '(\w+)'
        . '(?:\s*:\s*\w+)?'             // enum backing type
        . '(?:\s+extends\s+([\w\\\\]+))?' // extends
        . '(?:\s+implements\s+([\w\\\\,\s]+))?/';

    if (!preg_match($declPattern, $content, $declMatch)) {
        return null;
    }

    $modifier = $declMatch[1] ?? '';
    $type = $declMatch[2];
    $name = $declMatch[3];
    $extends = isset($declMatch[4]) && $declMatch[4] !== '' ? $declMatch[4] : null;
    $implements = isset($declMatch[5]) && $declMatch[5] !== ''
        ? array_map('trim', explode(',', $declMatch[5]))
        : [];

    $fqcn = $namespace . '\\' . $name;

    // Extract PHPDoc before declaration
    $description = extractPhpDoc($content, $type, $name);

    // Auto-generate description if none found
    if ($description === null) {
        $description = autoDescription($name, $type, $extends, $implements);
    }

    // Build relative path and GitHub URL
    $relativePath = ltrim(str_replace($repoRoot, '', $filePath), '/');
    $githubUrl = $githubBase . '/' . $relativePath;

    // Determine which library this belongs to
    $library = 'unknown';
    if (str_contains($relativePath, 'libs/Kernel/')) {
        $library = 'Kernel';
    } elseif (str_contains($relativePath, 'libs/API/')) {
        $library = 'API';
    } elseif (str_contains($relativePath, 'libs/Cli/')) {
        $library = 'Cli';
    } elseif (str_contains($relativePath, 'libs/McpServer/')) {
        $library = 'McpServer';
    } elseif (str_contains($relativePath, 'libs/Testing/')) {
        $library = 'Testing';
    } elseif (str_contains($relativePath, 'libs/Adapter/Symfony/')) {
        $library = 'Adapter/Symfony';
    } elseif (str_contains($relativePath, 'libs/Adapter/Laravel/')) {
        $library = 'Adapter/Laravel';
    } elseif (str_contains($relativePath, 'libs/Adapter/Yii3/')) {
        $library = 'Adapter/Yii3';
    } elseif (str_contains($relativePath, 'libs/Adapter/Yii2/')) {
        $library = 'Adapter/Yii2';
    } elseif (str_contains($relativePath, 'libs/Adapter/Cycle/')) {
        $library = 'Adapter/Cycle';
    }

    $entry = [
        'fqcn' => $fqcn,
        'short' => $name,
        'type' => $type,
        'library' => $library,
        'description' => $description,
        'github' => $githubUrl,
        'path' => $relativePath,
    ];

    if ($modifier !== '') {
        $entry['modifier'] = $modifier;
    }
    if ($extends !== null) {
        $entry['extends'] = $extends;
    }
    if ($implements !== []) {
        $entry['implements'] = $implements;
    }

    return $entry;
}

function extractPhpDoc(string $content, string $type, string $name): ?string
{
    // Match PHPDoc immediately before the class declaration
    $pattern = '/\/\*\*(.*?)\*\/\s*(?:(?:final|abstract|readonly)\s+)*'
        . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '/s';

    if (!preg_match($pattern, $content, $match)) {
        return null;
    }

    $docBlock = $match[1];
    $lines = explode("\n", $docBlock);
    $descriptionLines = [];

    foreach ($lines as $line) {
        $line = trim($line);
        $line = ltrim($line, '* ');
        $line = trim($line);

        // Skip empty lines at the start
        if ($line === '' && $descriptionLines === []) {
            continue;
        }

        // Stop at @tags or empty line after content
        if (str_starts_with($line, '@')) {
            break;
        }
        if ($line === '' && $descriptionLines !== []) {
            break;
        }

        $descriptionLines[] = $line;
    }

    $description = implode(' ', $descriptionLines);
    return $description !== '' ? $description : null;
}

function autoDescription(string $name, string $type, ?string $extends, array $implements): string
{
    // Collectors
    if (str_ends_with($name, 'Collector') && $name !== 'Collector') {
        $subject = preg_replace('/Collector$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "Collects {$subject} data during application lifecycle.";
    }

    // Proxies
    if (str_ends_with($name, 'Proxy')) {
        $subject = preg_replace('/Proxy$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "Decorator proxy for {$subject}. Intercepts calls and forwards data to collectors.";
    }

    // Controllers
    if (str_ends_with($name, 'Controller')) {
        $subject = preg_replace('/Controller$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "{$subject} inspector API controller.";
    }

    // Interfaces
    if ($type === 'interface') {
        $subject = preg_replace('/Interface$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "{$subject} contract.";
    }

    // Traits
    if ($type === 'trait') {
        $subject = preg_replace('/Trait$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "Provides {$subject} functionality.";
    }

    // Providers
    if (str_ends_with($name, 'Provider')) {
        $subject = preg_replace('/Provider$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "Provides {$subject} data.";
    }

    // Commands
    if (str_ends_with($name, 'Command')) {
        $subject = preg_replace('/Command$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "{$subject} CLI command.";
    }

    // Middleware
    if (str_ends_with($name, 'Middleware')) {
        $subject = preg_replace('/Middleware$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "{$subject} HTTP middleware.";
    }

    // Listeners
    if (str_ends_with($name, 'Listener')) {
        $subject = preg_replace('/Listener$/', '', $name);
        $subject = preg_replace('/([a-z])([A-Z])/', '$1 $2', $subject);
        return "{$subject} event listener.";
    }

    // Generic fallback with type
    return ucfirst($type) . ' ' . $name . '.';
}
