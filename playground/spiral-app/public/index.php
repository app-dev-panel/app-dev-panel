<?php

declare(strict_types=1);

use App\Application\Kernel;

// SSE / event-stream endpoints hold the request open and poll a socket. The
// per-request `max_execution_time` ceiling (default 30s on most builds) would
// otherwise kill them mid-poll. The ADP API endpoints are internal — disable
// the time limit unconditionally for the playground entry point.
set_time_limit(0);

// Strict error handler: every PHP warning / notice / deprecation becomes an
// ErrorException so E2E fixture tests fail on runtime quality issues instead of
// silently logging them. Do not remove — see CLAUDE.md "Zero Tolerance".
//
// Two carve-outs:
//  * `socket_recv()` on a non-blocking socket legitimately emits an
//    "Interrupted system call" warning when the polling loop is signalled — that
//    is the LiveEventStream's normal exit path, not a bug.
//  * Anything originating from the ADP API library is internal infrastructure,
//    not playground code; we don't want the playground's strictness to mask
//    real adapter bugs as 500s either.
error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    if (str_contains($message, 'Interrupted system call')) {
        return true;
    }
    if (str_contains($file, '/libs/API/src/') || str_contains($file, '/app-dev-panel/api/src/')) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel();
$kernel->run();
