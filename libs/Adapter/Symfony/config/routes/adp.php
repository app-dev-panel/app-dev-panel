<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
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
};
