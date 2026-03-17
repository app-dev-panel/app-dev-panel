<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AppDevPanelBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Priority -64: must run AFTER Symfony's LoggerPass (priority -32)
        // which registers the 'logger' service, so we can decorate it.
        $container->addCompilerPass(new CollectorProxyCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -64);
    }

    public function getContainerExtension(): AppDevPanelExtension
    {
        return new AppDevPanelExtension();
    }
}
