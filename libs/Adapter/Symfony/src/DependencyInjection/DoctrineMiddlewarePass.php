<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Proxy\DoctrineDbalMiddleware;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the DoctrineDbalMiddleware with the `doctrine.middleware` tag.
 *
 * Must run BEFORE DoctrineBundle's MiddlewaresPass (which registers at priority 0),
 * so this pass is added at priority 10. The main CollectorProxyCompilerPass cannot
 * do the registration because it runs at priority -64 (to decorate 'logger' after
 * Symfony's LoggerPass), by which point MiddlewaresPass has already iterated the
 * `doctrine.middleware` tag and moved on.
 */
final class DoctrineMiddlewarePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('app_dev_panel.enabled')) {
            return;
        }

        if (!$container->has(DatabaseCollector::class)) {
            return;
        }

        if (!interface_exists(Middleware::class)) {
            return;
        }

        if (!$container->has('doctrine.dbal.default_connection')) {
            return;
        }

        $container
            ->register(DoctrineDbalMiddleware::class, DoctrineDbalMiddleware::class)
            ->setArguments([new Reference(DatabaseCollector::class)])
            ->addTag('doctrine.middleware')
            ->setPublic(false);
    }
}
