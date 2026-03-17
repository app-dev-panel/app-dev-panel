<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\VarDumper\Handler\CompositeHandler;
use Yiisoft\VarDumper\VarDumper;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Adapter\Yiisoft\Proxy\VarDumperHandlerInterfaceProxy;
use AppDevPanel\Kernel\DebugServer\VarDumperHandler;

/**
 * @var $params array
 */

return [
    static function (ContainerInterface $container) use ($params) {
        require_once __DIR__ . '/helpers.php';

        if (!isAppDevPanelEnabled($params)) {
            return;
        }
        if (!$container->has(VarDumperCollector::class)) {
            return;
        }

        $decorated = VarDumper::getDefaultHandler();

        if ($params['app-dev-panel/yii-debug']['devServer']['enabled'] ?? false) {
            $decorated = new CompositeHandler([$decorated, new VarDumperHandler()]);
        }

        VarDumper::setDefaultHandler(
            new VarDumperHandlerInterfaceProxy(
                $decorated,
                $container->get(VarDumperCollector::class),
            ),
        );
    },
];
