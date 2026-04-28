#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Timeout invariant checker.
 *
 * Verifies that timeouts in Makefile, phpunit.xml.dist files, and Vitest configs
 * match the canonical table in CLAUDE.md ("Hard Timeouts — NEVER RAISE").
 *
 * Usage:
 *   php tools/check-timeouts.php
 *   php tools/check-timeouts.php --format=github
 *
 * Exit codes:
 *   0 — all timeouts match the table
 *   1 — at least one timeout is missing or larger than the canonical value
 *   2 — configuration error
 */

$rootDir = dirname(__DIR__);
$format = 'plain';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
}

/**
 * Each rule:
 *   file       — path relative to project root
 *   pattern    — regex with one numeric capture group
 *   max        — maximum allowed value
 *   label      — human-readable label
 */
$rules = [
    // Makefile suite-level timeouts
    [
        'file' => 'Makefile',
        'pattern' => '/^TEST_TIMEOUT\s*\?=\s*(\d+)/m',
        'max' => 180,
        'label' => 'Makefile TEST_TIMEOUT',
    ],
    [
        'file' => 'Makefile',
        'pattern' => '/^FIXTURE_TIMEOUT\s*\?=\s*(\d+)/m',
        'max' => 120,
        'label' => 'Makefile FIXTURE_TIMEOUT',
    ],
    [
        'file' => 'Makefile',
        'pattern' => '/^HELPER_TIMEOUT\s*\?=\s*(\d+)/m',
        'max' => 15,
        'label' => 'Makefile HELPER_TIMEOUT',
    ],
    // Vitest configs
    [
        'file' => 'libs/frontend/vitest.config.ts',
        'pattern' => '/testTimeout:\s*([0-9_]+)/',
        'max' => 10_000,
        'label' => 'Vitest jsdom testTimeout',
    ],
    [
        'file' => 'libs/frontend/vitest.config.ts',
        'pattern' => '/hookTimeout:\s*([0-9_]+)/',
        'max' => 10_000,
        'label' => 'Vitest jsdom hookTimeout',
    ],
    [
        'file' => 'libs/frontend/vitest.browser.config.ts',
        'pattern' => '/testTimeout:\s*([0-9_]+)/',
        'max' => 15_000,
        'label' => 'Vitest browser testTimeout',
    ],
    [
        'file' => 'libs/frontend/vitest.browser.config.ts',
        'pattern' => '/hookTimeout:\s*([0-9_]+)/',
        'max' => 15_000,
        'label' => 'Vitest browser hookTimeout',
    ],
];

// PHPUnit configs share the same expected values.
$phpunitConfigs = [];
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
        static function (SplFileInfo $f): bool {
            $skip = ['vendor', 'node_modules', '.git', '.idea', 'dist', 'coverage', 'playground'];
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

$phpunitRules = [
    ['attr' => 'defaultTimeLimit',     'max' => 10, 'label' => 'phpunit defaultTimeLimit'],
    ['attr' => 'timeoutForSmallTests', 'max' => 2,  'label' => 'phpunit timeoutForSmallTests'],
    ['attr' => 'timeoutForMediumTests','max' => 5,  'label' => 'phpunit timeoutForMediumTests'],
    ['attr' => 'timeoutForLargeTests', 'max' => 10, 'label' => 'phpunit timeoutForLargeTests'],
];
$phpunitIniRules = [
    ['name' => 'max_execution_time',    'max' => 15, 'label' => 'phpunit max_execution_time'],
    ['name' => 'default_socket_timeout','max' => 10, 'label' => 'phpunit default_socket_timeout'],
];

foreach ($phpunitConfigs as $cfg) {
    $rel = substr($cfg, strlen($rootDir) + 1);
    foreach ($phpunitRules as $r) {
        $rules[] = [
            'file' => $rel,
            'pattern' => '/' . preg_quote($r['attr'], '/') . '\s*=\s*"(\d+)"/',
            'max' => $r['max'],
            'label' => $r['label'],
        ];
    }
    foreach ($phpunitIniRules as $r) {
        $rules[] = [
            'file' => $rel,
            'pattern' => '/<ini\s+name="' . preg_quote($r['name'], '/') . '"\s+value="(\d+)"/',
            'max' => $r['max'],
            'label' => $r['label'],
        ];
    }
}

$violations = [];

foreach ($rules as $rule) {
    $abs = $rootDir . '/' . $rule['file'];
    if (!is_file($abs)) {
        $violations[] = [
            'file' => $rule['file'],
            'line' => 1,
            'message' => "{$rule['label']}: file missing",
        ];
        continue;
    }
    $contents = (string) file_get_contents($abs);
    if (!preg_match($rule['pattern'], $contents, $m)) {
        $violations[] = [
            'file' => $rule['file'],
            'line' => 1,
            'message' => "{$rule['label']}: not found (pattern {$rule['pattern']})",
        ];
        continue;
    }
    $actual = (int) str_replace('_', '', $m[1]);
    if ($actual > $rule['max']) {
        // Find line number for nicer reporting.
        $lineNo = 1;
        $offset = strpos($contents, $m[0]);
        if ($offset !== false) {
            $lineNo = substr_count(substr($contents, 0, $offset), "\n") + 1;
        }
        $violations[] = [
            'file' => $rule['file'],
            'line' => $lineNo,
            'message' => "{$rule['label']}: actual={$actual}, max allowed={$rule['max']} (CLAUDE.md \"Hard Timeouts\")",
        ];
    }
}

if ($violations === []) {
    if ($format !== 'github') {
        echo "OK — all timeouts at or below the CLAUDE.md ceiling.\n";
    }
    exit(0);
}

if ($format === 'github') {
    foreach ($violations as $v) {
        printf("::error file=%s,line=%d::%s\n", $v['file'], $v['line'], $v['message']);
    }
} else {
    echo "Timeout violations (" . count($violations) . "):\n";
    foreach ($violations as $v) {
        printf("  %s:%d  %s\n", $v['file'], $v['line'], $v['message']);
    }
}

exit(1);
