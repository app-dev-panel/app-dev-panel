<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\VarDumper\Handler\CompositeHandler;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Collector\VarDumperCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperHandlerInterfaceProxy;
use Yiisoft\Yii\Debug\DebugServer\VarDumperHandler;

/**
 * @var $params array
 */

return [
    static function (ContainerInterface $container) use ($params) {
        if (!($params['yiisoft/yii-debug']['enabled'] ?? false)) {
            return;
        }
        if (!$container->has(VarDumperCollector::class)) {
            return;
        }

        $decorated = VarDumper::getDefaultHandler();

        if ($params['yiisoft/yii-debug']['devServer']['enabled'] ?? false) {
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
