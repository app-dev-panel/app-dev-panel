<?php

declare(strict_types=1);

use App\Application\Kernel;

// Strict error handler: every PHP warning / notice / deprecation becomes an ErrorException
// so E2E fixture tests fail on runtime quality issues instead of silently logging them.
// Do not remove — see CLAUDE.md "Zero Tolerance".
error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel();
$kernel->run();
