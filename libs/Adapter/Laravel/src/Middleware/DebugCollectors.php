<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Middleware;

use AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor;
use AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;

/**
 * Groups optional collector dependencies for DebugMiddleware.
 */
final readonly class DebugCollectors
{
    public function __construct(
        public ?RequestCollector $request = null,
        public ?WebAppInfoCollector $webAppInfo = null,
        public ?ExceptionCollector $exception = null,
        public ?VarDumperCollector $varDumper = null,
        public ?EnvironmentCollector $environment = null,
        public ?RouterDataExtractor $routerDataExtractor = null,
        public ?ViteAssetListener $viteAssetListener = null,
    ) {}
}
