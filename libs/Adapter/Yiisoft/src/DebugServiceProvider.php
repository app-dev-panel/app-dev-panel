<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft;

use AppDevPanel\Kernel\Collector\ContainerInterfaceProxy;
use AppDevPanel\Kernel\Collector\ContainerProxyConfig;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class DebugServiceProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
            ContainerInterface::class => static fn(ContainerInterface $container) => new ContainerInterfaceProxy(
                $container,
                $container->get(ContainerProxyConfig::class),
            ),
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }
}
