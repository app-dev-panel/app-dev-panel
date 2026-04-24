<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Adapter\Symfony\Controller\AdpAssetController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    // Asset route — serves panel/toolbar bundles shipped by app-dev-panel/frontend-assets.
    // Must come before any /debug/* catch-all.
    $routes
        ->add('adp_assets', '/debug-assets/{path}')
        ->controller(AdpAssetController::class)
        ->requirements(['path' => '.+'])
        ->methods(['GET']);

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

    // Panel routes — serves the embedded SPA (catch-all for client-side routing)
    $routes
        ->add('adp_panel', '/debug/{path}')
        ->controller(AdpApiController::class)
        ->requirements(['path' => '(?!api(/|$)).+'])
        ->defaults(['path' => ''])
        ->methods(['GET']);

    $routes->add('adp_panel_root', '/debug')->controller(AdpApiController::class)->methods(['GET']);
};
