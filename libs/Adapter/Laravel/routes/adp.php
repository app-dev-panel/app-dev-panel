<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Laravel\Controller\AdpApiController;
use Illuminate\Support\Facades\Route;

/**
 * ADP routes.
 *
 * Routes all /debug/api/*, /inspect/api/* paths to the AdpApiController
 * which bridges to the framework-agnostic ADP ApiApplication.
 * Also routes /debug and /debug/* (non-API) for the embedded panel SPA.
 */
Route::any('/debug/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/inspect/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Panel SPA — catch-all for client-side routing (must be after /debug/api to avoid conflicts)
Route::get('/debug/{path?}', AdpApiController::class)
    ->where('path', '(?!api(/|$)).*')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
