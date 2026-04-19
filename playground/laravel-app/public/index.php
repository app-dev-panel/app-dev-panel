<?php

declare(strict_types=1);

use Illuminate\Http\Request;

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

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle($request = Request::capture());

$response->send();

$kernel->terminate($request, $response);
