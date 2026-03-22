<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Laravel\Controller\AdpApiController;
use Illuminate\Support\Facades\Route;

/**
 * ADP API routes.
 *
 * Routes all /debug/api/* and /inspect/api/* paths to the AdpApiController
 * which bridges to the framework-agnostic ADP ApiApplication.
 */
Route::any('/debug/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/inspect/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
