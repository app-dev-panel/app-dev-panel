#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Test-strictness invariant checker.
 *
 * Enforces CLAUDE.md "Zero Tolerance" rules:
 *   1. All phpunit.xml.dist files keep failOn* / beStrictAbout* attributes set to "true".
 *   2. No test calls markTestSkipped() / markTestIncomplete() / expectDeprecation*().
 *
 * Usage:
 *   php tools/check-test-strictness.php
 *   php tools/check-test-strictness.php --format=github
 *
 * Exit codes:
 *   0 — clean
 *   1 — violations found
 *   2 — configuration error
 */

$rootDir = dirname(__DIR__);
$format = 'plain';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
}

$requiredStrictAttributes = [
    'failOnRisky',
    'failOnWarning',
    'failOnNotice',
    'failOnDeprecation',
    'failOnPhpunitDeprecation',
    'failOnPhpunitWarning',
    'failOnIncomplete',
    'failOnSkipped',
    'failOnEmptyTestSuite',
    'beStrictAboutOutputDuringTests',
    'beStrictAboutTestsThatDoNotTestAnything',
    'beStrictAboutChangesToGlobalState',
];

$bannedCalls = [
    'markTestSkipped',
    'markTestIncomplete',
    'expectDeprecation',
    'expectDeprecationMessage',
    'expectDeprecationMessageMatches',
];

$violations = [];

// --- 1. PHPUnit config attributes -------------------------------------------------

$phpunitConfigs = [];
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
        static function (SplFileInfo $f): bool {
            $skip = ['vendor', 'node_modules', '.git', '.idea', 'dist', 'coverage'];
            return !($f->isDir() && in_array($f->getFilename(), $skip, true));
        },
    ),
);
foreach ($it as $file) {
    if ($file->isFile() && $file->getFilename() === 'phpunit.xml.dist') {
        $phpunitConfigs[] = $file->getPathname();
    }
}
sort($phpunitConfigs);

if ($phpunitConfigs === []) {
    fwrite(STDERR, "No phpunit.xml.dist files found.\n");
    exit(2);
}

foreach ($phpunitConfigs as $configPath) {
    $xml = @simplexml_load_file($configPath);
    if ($xml === false) {
        $violations[] = [
            'file' => $configPath,
            'line' => 1,
            'message' => "Cannot parse XML",
        ];
        continue;
    }
    $attrs = $xml->attributes();
    foreach ($requiredStrictAttributes as $name) {
        $value = isset($attrs[$name]) ? (string) $attrs[$name] : null;
        if ($value !== 'true') {
            $violations[] = [
                'file' => $configPath,
                'line' => 1,
                'message' => "Strict attribute `{$name}` must be \"true\" (got " . ($value ?? 'unset') . ")",
            ];
        }
    }
}

// --- 2. Banned PHPUnit calls -------------------------------------------------------

$testRoots = [
    $rootDir . '/libs',
    $rootDir . '/tests',
    $rootDir . '/playground',
];

$bannedPattern = '/\b(' . implode('|', array_map('preg_quote', $bannedCalls)) . ')\s*\(/';

foreach ($testRoots as $root) {
    if (!is_dir($root)) {
        continue;
    }
    $rit = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            static function (SplFileInfo $f): bool {
                $skip = ['vendor', 'node_modules', '.git', 'dist', 'coverage', 'runtime', 'storage'];
                return !($f->isDir() && in_array($f->getFilename(), $skip, true));
            },
        ),
    );
    foreach ($rit as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        // Only scan test files.
        if (!str_contains($path, '/tests/') && !str_ends_with($path, 'Test.php')) {
            continue;
        }
        $contents = (string) file_get_contents($path);
        if ($contents === '' || !preg_match($bannedPattern, $contents)) {
            continue;
        }
        $lines = explode("\n", $contents);
        foreach ($lines as $idx => $line) {
            // Strip line comments so we don't flag examples in docblocks/comments.
            $stripped = preg_replace('#//.*$#', '', $line);
            $stripped = preg_replace('#/\*.*?\*/#', '', (string) $stripped);
            if (!preg_match($bannedPattern, (string) $stripped, $m)) {
                continue;
            }
            $violations[] = [
                'file' => $path,
                'line' => $idx + 1,
                'message' => "Banned call `{$m[1]}()` — see CLAUDE.md \"Zero Tolerance\".",
            ];
        }
    }
}

// --- Output ------------------------------------------------------------------------

if ($violations === []) {
    if ($format !== 'github') {
        echo "OK — phpunit strictness attributes set, no banned calls found.\n";
    }
    exit(0);
}

if ($format === 'github') {
    foreach ($violations as $v) {
        $rel = str_starts_with($v['file'], $rootDir . '/') ? substr($v['file'], strlen($rootDir) + 1) : $v['file'];
        printf("::error file=%s,line=%d::%s\n", $rel, $v['line'], $v['message']);
    }
} else {
    echo "Test-strictness violations (" . count($violations) . "):\n";
    foreach ($violations as $v) {
        $rel = str_starts_with($v['file'], $rootDir . '/') ? substr($v['file'], strlen($rootDir) + 1) : $v['file'];
        printf("  %s:%d  %s\n", $rel, $v['line'], $v['message']);
    }
}

exit(1);
