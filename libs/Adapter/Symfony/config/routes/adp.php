<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
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

    // Panel routes — serves the embedded SPA (catch-all for client-side routing).
    // Static assets (panel + toolbar bundle) are NOT served by PHP — run
    // `bin/console app-dev-panel:assets:install` to copy or symlink them into
    // `public/bundles/appdevpanel/`, then let the web server (nginx/Apache)
    // serve them directly.
    $routes
        ->add('adp_panel', '/debug/{path}')
        ->controller(AdpApiController::class)
        ->requirements(['path' => '(?!api(/|$)).+'])
        ->defaults(['path' => ''])
        ->methods(['GET']);

    $routes->add('adp_panel_root', '/debug')->controller(AdpApiController::class)->methods(['GET']);
};
