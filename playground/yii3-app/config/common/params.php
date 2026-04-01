<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

return [
    'app-dev-panel/yii3' => [
        'collectors' => [
            \AppDevPanel\Kernel\Collector\EnvironmentCollector::class,
            \AppDevPanel\Kernel\Collector\LogCollector::class,
            \AppDevPanel\Kernel\Collector\EventCollector::class,
            \AppDevPanel\Kernel\Collector\ServiceCollector::class,
            \AppDevPanel\Kernel\Collector\HttpClientCollector::class,
            \AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector::class,
            \AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector::class,
            \AppDevPanel\Kernel\Collector\ExceptionCollector::class,
            \AppDevPanel\Kernel\Collector\VarDumperCollector::class,
            \AppDevPanel\Kernel\Collector\TimelineCollector::class,
            \AppDevPanel\Kernel\Collector\DatabaseCollector::class,
            \AppDevPanel\Kernel\Collector\MailerCollector::class,
            \AppDevPanel\Kernel\Collector\OpenTelemetryCollector::class,
        ],
    ],

    'application' => require __DIR__ . '/application.php',

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];
