<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Adapter\Symfony\Collector\RouterDataExtractor;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;

/**
 * Groups the optional collectors injected into {@see HttpSubscriber}.
 *
 * Reduces the constructor parameter count and keeps collector references cohesive.
 */
final readonly class HttpSubscriberCollectors
{
    public function __construct(
        public ?RequestCollector $request = null,
        public ?WebAppInfoCollector $webAppInfo = null,
        public ?ExceptionCollector $exception = null,
        public ?VarDumperCollector $varDumper = null,
        public ?EnvironmentCollector $environment = null,
        public ?RouterDataExtractor $routerDataExtractor = null,
    ) {}
}
