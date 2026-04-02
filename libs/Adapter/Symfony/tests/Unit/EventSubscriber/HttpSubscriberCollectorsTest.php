<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\Collector\RouterDataExtractor;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriberCollectors;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use PHPUnit\Framework\TestCase;

final class HttpSubscriberCollectorsTest extends TestCase
{
    public function testAllCollectorsNullByDefault(): void
    {
        $collectors = new HttpSubscriberCollectors();

        $this->assertNull($collectors->request);
        $this->assertNull($collectors->webAppInfo);
        $this->assertNull($collectors->exception);
        $this->assertNull($collectors->varDumper);
        $this->assertNull($collectors->environment);
        $this->assertNull($collectors->routerDataExtractor);
    }

    public function testWithRequestCollector(): void
    {
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);

        $collectors = new HttpSubscriberCollectors(request: $requestCollector);

        $this->assertSame($requestCollector, $collectors->request);
        $this->assertNull($collectors->webAppInfo);
        $this->assertNull($collectors->exception);
    }

    public function testWithMultipleCollectors(): void
    {
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $webAppInfo = new WebAppInfoCollector($timeline);
        $exceptionCollector = new ExceptionCollector($timeline);
        $varDumperCollector = new VarDumperCollector($timeline);

        $collectors = new HttpSubscriberCollectors(
            request: $requestCollector,
            webAppInfo: $webAppInfo,
            exception: $exceptionCollector,
            varDumper: $varDumperCollector,
        );

        $this->assertSame($requestCollector, $collectors->request);
        $this->assertSame($webAppInfo, $collectors->webAppInfo);
        $this->assertSame($exceptionCollector, $collectors->exception);
        $this->assertSame($varDumperCollector, $collectors->varDumper);
        $this->assertNull($collectors->environment);
        $this->assertNull($collectors->routerDataExtractor);
    }

    public function testIsReadonly(): void
    {
        $collectors = new HttpSubscriberCollectors();

        $reflection = new \ReflectionClass($collectors);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testWithEnvironmentCollector(): void
    {
        $timeline = new TimelineCollector();
        $environmentCollector = new EnvironmentCollector();

        $collectors = new HttpSubscriberCollectors(environment: $environmentCollector);

        $this->assertSame($environmentCollector, $collectors->environment);
        $this->assertNull($collectors->request);
        $this->assertNull($collectors->routerDataExtractor);
    }

    public function testWithRouterDataExtractor(): void
    {
        $timeline = new TimelineCollector();
        $routerCollector = new RouterCollector();
        $routerDataExtractor = new RouterDataExtractor($routerCollector);

        $collectors = new HttpSubscriberCollectors(routerDataExtractor: $routerDataExtractor);

        $this->assertSame($routerDataExtractor, $collectors->routerDataExtractor);
        $this->assertNull($collectors->request);
        $this->assertNull($collectors->environment);
    }

    public function testWithAllCollectors(): void
    {
        $timeline = new TimelineCollector();
        $requestCollector = new RequestCollector($timeline);
        $webAppInfo = new WebAppInfoCollector($timeline);
        $exceptionCollector = new ExceptionCollector($timeline);
        $varDumperCollector = new VarDumperCollector($timeline);
        $environmentCollector = new EnvironmentCollector();
        $routerCollector = new RouterCollector();
        $routerDataExtractor = new RouterDataExtractor($routerCollector);

        $collectors = new HttpSubscriberCollectors(
            request: $requestCollector,
            webAppInfo: $webAppInfo,
            exception: $exceptionCollector,
            varDumper: $varDumperCollector,
            environment: $environmentCollector,
            routerDataExtractor: $routerDataExtractor,
        );

        $this->assertSame($requestCollector, $collectors->request);
        $this->assertSame($webAppInfo, $collectors->webAppInfo);
        $this->assertSame($exceptionCollector, $collectors->exception);
        $this->assertSame($varDumperCollector, $collectors->varDumper);
        $this->assertSame($environmentCollector, $collectors->environment);
        $this->assertSame($routerDataExtractor, $collectors->routerDataExtractor);
    }
}
