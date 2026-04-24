<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Laravel\Controller\AdpApiController;
use AppDevPanel\Adapter\Laravel\Controller\FrontendAssetsController;
use AppDevPanel\FrontendAssets\FrontendAssets;
use Illuminate\Support\Facades\Route;

/**
 * ADP routes.
 *
 * Routes all /debug/api/*, /inspect/api/* paths to the AdpApiController
 * which bridges to the framework-agnostic ADP ApiApplication.
 * Also routes /debug and /debug/* (non-API) for the embedded panel SPA,
 * and /vendor/app-dev-panel/* for the prebuilt panel/toolbar bundles
 * shipped in the app-dev-panel/frontend-assets Composer package.
 */

// Laravel 13 renamed VerifyCsrfToken to PreventRequestForgery
$csrfMiddleware = class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
    ? \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class
    : \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class;

Route::any('/debug/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([$csrfMiddleware]);

Route::any('/inspect/api/{path?}', AdpApiController::class)
    ->where('path', '.*')
    ->middleware(['api'])
    ->withoutMiddleware([$csrfMiddleware]);

// Serve the prebuilt panel + toolbar straight from the frontend-assets Composer package.
// Registered only when the bundle is present so the framework's own /vendor/* paths
// (if any) are not shadowed on installations without the package.
if (FrontendAssets::exists()) {
    Route::get('/vendor/app-dev-panel/{file}', FrontendAssetsController::class)->where(
        'file',
        '.+',
    )->withoutMiddleware([$csrfMiddleware]);
}

// Panel SPA — catch-all for client-side routing (must be after /debug/api to avoid conflicts)
Route::get('/debug/{path?}', AdpApiController::class)->where('path', '(?!api(/|$)).*')->withoutMiddleware([
    $csrfMiddleware,
]);
