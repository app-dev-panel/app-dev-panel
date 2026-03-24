<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Middleware;

use AppDevPanel\Adapter\Laravel\Middleware\DebugCollectors;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use PHPUnit\Framework\TestCase;

final class DebugCollectorsTest extends TestCase
{
    public function testAllPropertiesDefaultToNull(): void
    {
        $collectors = new DebugCollectors();

        $this->assertNull($collectors->request);
        $this->assertNull($collectors->webAppInfo);
        $this->assertNull($collectors->exception);
        $this->assertNull($collectors->varDumper);
        $this->assertNull($collectors->environment);
        $this->assertNull($collectors->routerDataExtractor);
    }

    public function testPropertiesCanBeSet(): void
    {
        $timeline = new TimelineCollector();
        $request = new RequestCollector($timeline);
        $webAppInfo = new WebAppInfoCollector($timeline, 'Laravel');
        $exception = new ExceptionCollector($timeline);

        $collectors = new DebugCollectors(request: $request, webAppInfo: $webAppInfo, exception: $exception);

        $this->assertSame($request, $collectors->request);
        $this->assertSame($webAppInfo, $collectors->webAppInfo);
        $this->assertSame($exception, $collectors->exception);
        $this->assertNull($collectors->varDumper);
    }
}
