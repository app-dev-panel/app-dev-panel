<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony;

use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
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

        $container->addCompilerPass(new CollectorProxyCompilerPass());
    }

    public function getContainerExtension(): AppDevPanelExtension
    {
        return new AppDevPanelExtension();
    }
}
