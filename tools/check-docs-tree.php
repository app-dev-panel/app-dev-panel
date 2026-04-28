#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * VitePress documentation tree checker.
 *
 * 1. Every `link: "/path"` reference in `website/.vitepress/config.ts` must resolve
 *    to a real `.md` file under `website/`.
 * 2. Every English page under `website/{guide,api,blog}/` must have a Russian
 *    counterpart under `website/ru/...` (and vice versa). Missing translations
 *    are reported but do not fail by default — pass `--fail-on-missing-ru` to
 *    treat them as errors.
 *
 * Usage:
 *   php tools/check-docs-tree.php
 *   php tools/check-docs-tree.php --format=github
 *   php tools/check-docs-tree.php --fail-on-missing-ru
 *
 * Exit codes:
 *   0 — clean (or only soft warnings)
 *   1 — broken sidebar links, or missing translations with --fail-on-missing-ru
 *   2 — configuration error (config.ts missing, etc.)
 */

$rootDir = dirname(__DIR__);
$websiteDir = $rootDir . '/website';
$configFile = $websiteDir . '/.vitepress/config.ts';

$format = 'plain';
$failOnMissingRu = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
    if ($arg === '--fail-on-missing-ru') {
        $failOnMissingRu = true;
    }
}

if (!is_file($configFile)) {
    fwrite(STDERR, "Error: {$configFile} not found.\n");
    exit(2);
}

$violations = [];
$warnings = [];

// --- 1. Sidebar links → markdown files --------------------------------------------

$config = (string) file_get_contents($configFile);
preg_match_all('/link:\s*"([^"]+)"/', $config, $matches, PREG_OFFSET_CAPTURE);

foreach ($matches[1] as $m) {
    $link = $m[0];
    $offset = $m[1];

    // Skip external links and pure anchors.
    if (!str_starts_with($link, '/') || str_starts_with($link, '/#')) {
        continue;
    }

    $clean = strtok($link, '#'); // drop fragment
    if ($clean === false || $clean === '/') {
        continue;
    }
    // Strip locale prefix; resolution is relative to website/.
    $rel = ltrim($clean, '/');

    $candidates = [
        $websiteDir . '/' . $rel . '.md',
        $websiteDir . '/' . rtrim($rel, '/') . '/index.md',
    ];

    $found = false;
    foreach ($candidates as $c) {
        if (is_file($c)) {
            $found = true;
            break;
        }
    }
    if ($found) {
        continue;
    }

    $lineNo = substr_count(substr($config, 0, $offset), "\n") + 1;
    $violations[] = [
        'file' => 'website/.vitepress/config.ts',
        'line' => $lineNo,
        'message' => "Sidebar link `{$link}` does not resolve to a markdown file under website/.",
    ];
}

// --- 2. EN ↔ RU symmetry ----------------------------------------------------------

$enDirs = ['guide', 'api', 'blog'];
$enPages = [];
$ruPages = [];

foreach ($enDirs as $dir) {
    $enRoot = $websiteDir . '/' . $dir;
    $ruRoot = $websiteDir . '/ru/' . $dir;
    foreach (collectMarkdown($enRoot) as $rel) {
        $enPages[$dir . '/' . $rel] = true;
    }
    foreach (collectMarkdown($ruRoot) as $rel) {
        $ruPages[$dir . '/' . $rel] = true;
    }
}

foreach (array_keys($enPages) as $rel) {
    if (!isset($ruPages[$rel])) {
        $msg = "Missing Russian translation: website/ru/{$rel} (EN exists at website/{$rel})";
        $entry = [
            'file' => 'website/' . $rel,
            'line' => 1,
            'message' => $msg,
        ];
        if ($failOnMissingRu) {
            $violations[] = $entry;
        } else {
            $warnings[] = $entry;
        }
    }
}
foreach (array_keys($ruPages) as $rel) {
    if (!isset($enPages[$rel])) {
        $entry = [
            'file' => 'website/ru/' . $rel,
            'line' => 1,
            'message' => "Russian page has no English original: website/{$rel}",
        ];
        // Stale RU pages are always real bugs, not just translation lag.
        $violations[] = $entry;
    }
}

// --- Output ------------------------------------------------------------------------

function collectMarkdown(string $root): array {
    if (!is_dir($root)) {
        return [];
    }
    $found = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            $found[] = ltrim(substr($file->getPathname(), strlen($root)), '/');
        }
    }
    sort($found);
    return $found;
}

if ($format === 'github') {
    foreach ($violations as $v) {
        printf("::error file=%s,line=%d::%s\n", $v['file'], $v['line'], $v['message']);
    }
    foreach ($warnings as $w) {
        printf("::warning file=%s,line=%d::%s\n", $w['file'], $w['line'], $w['message']);
    }
} else {
    if ($warnings !== []) {
        echo "Warnings (" . count($warnings) . "):\n";
        foreach ($warnings as $w) {
            printf("  %s:%d  %s\n", $w['file'], $w['line'], $w['message']);
        }
        echo "\n";
    }
    if ($violations !== []) {
        echo "Errors (" . count($violations) . "):\n";
        foreach ($violations as $v) {
            printf("  %s:%d  %s\n", $v['file'], $v['line'], $v['message']);
        }
    }
    if ($violations === [] && $warnings === []) {
        echo "OK — sidebar links resolve, EN/RU pages in sync.\n";
    }
}

exit($violations === [] ? 0 : 1);
