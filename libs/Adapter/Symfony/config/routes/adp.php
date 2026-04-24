<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Adapter\Symfony\Controller\FrontendAssetsController;
use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    // Serve the prebuilt panel + toolbar straight from the frontend-assets Composer
    // package. The URL matches Symfony's historical assets:install layout, so a raw
    // webserver fallback via try_files still wins when users copy the bundle into
    // public/bundles/appdevpanel/.
    if (FrontendAssets::exists()) {
        $routes
            ->add('adp_frontend_assets', '/bundles/appdevpanel/{file}')
            ->controller(FrontendAssetsController::class)
            ->requirements(['file' => '.+'])
            ->methods(['GET']);
    }

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
