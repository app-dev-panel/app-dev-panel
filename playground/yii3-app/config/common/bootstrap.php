<?php

declare(strict_types=1);

use App\Auth\RbacSeeder;
use Psr\Container\ContainerInterface;

/**
 * @psalm-var list<callable(ContainerInterface): void>
 */
return [
    static function (ContainerInterface $container): void {
        $container->get(RbacSeeder::class)->seed();
    },
];
