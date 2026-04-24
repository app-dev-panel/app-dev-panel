<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Adapter\Symfony\Controller\AdpAssetsController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    // API routes (must be registered before the panel catch-all)
    $routes
        ->add('adp_debug_api', '/debug/api/{path}')
        ->controller(AdpApiController::class)
        ->requirements(['path' => '.+'])
        ->defaults(['path' => ''])
        ->methods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);

    $routes
        ->add('adp_debug_api_root', '/debug/api')
        ->controller(AdpApiController::class)
        ->methods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);

    $routes
        ->add('adp_inspect_api', '/inspect/api/{path}')
        ->controller(AdpApiController::class)
        ->requirements(['path' => '.+'])
        ->defaults(['path' => ''])
        ->methods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);

    $routes
        ->add('adp_inspect_api_root', '/inspect/api')
        ->controller(AdpApiController::class)
        ->methods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']);

    // Static assets — streams the panel/toolbar bundle from `app-dev-panel/frontend-assets`.
    // Registered unconditionally; the controller 404s when the package is missing.
    $routes
        ->add('adp_assets', '/_adp-assets/{path}')
        ->controller(AdpAssetsController::class)
        ->requirements(['path' => '.+'])
        ->methods(['GET']);

    // Panel routes — serves the embedded SPA (catch-all for client-side routing)
    $routes
        ->add('adp_panel', '/debug/{path}')
        ->controller(AdpApiController::class)
        ->requirements(['path' => '(?!api(/|$)).+'])
        ->defaults(['path' => ''])
        ->methods(['GET']);

    $routes->add('adp_panel_root', '/debug')->controller(AdpApiController::class)->methods(['GET']);
};
