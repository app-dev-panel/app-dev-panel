<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3;

use AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ContainerProxyConfig;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class DebugServiceProvider implements ServiceProviderInterface
{
    private static bool $resolving = false;

    public function getDefinitions(): array
    {
        return [
            ContainerInterface::class => static function (ContainerInterface $container): ContainerInterface {
                if (self::$resolving) {
                    return $container;
                }

                self::$resolving = true;
                try {
                    return new ContainerInterfaceProxy($container, $container->get(ContainerProxyConfig::class));
                } finally {
                    self::$resolving = false;
                }
            },
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }
}
