<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Router;

use AppDevPanel\Adapter\Yii3\Collector\Router\UrlMatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\RouterCollector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlMatcherInterface;

final class UrlMatcherInterfaceProxyTest extends TestCase
{
    public function testMatchDelegatesToDecoratedAndCollectsMatchTime(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $route = Route::get('/test');
        $matchingResult = MatchingResult::fromSuccess($route, []);

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher->expects($this->once())->method('match')->with($request)->willReturn($matchingResult);

        $collector = new RouterCollector();
        $collector->startup();

        $proxy = new UrlMatcherInterfaceProxy($urlMatcher, $collector);
        $result = $proxy->match($request);

        $this->assertSame($matchingResult, $result);
    }

    public function testMatchCollectsPositiveMatchTime(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $route = Route::get('/test');
        $matchingResult = MatchingResult::fromSuccess($route, []);

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher
            ->method('match')
            ->willReturnCallback(function () use ($matchingResult) {
                usleep(1000);
                return $matchingResult;
            });

        $collector = new RouterCollector();
        $collector->startup();

        $proxy = new UrlMatcherInterfaceProxy($urlMatcher, $collector);
        $proxy->match($request);

        // Trigger routes collection to expose matchTime in getCollected
        $collector->collectRoutes([], null);
        $collected = $collector->getCollected();
        $this->assertArrayHasKey('routeTime', $collected);
        $this->assertGreaterThan(0, $collected['routeTime']);
    }

    public function testMatchReturnsFailureResult(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $matchingResult = MatchingResult::fromFailure(['GET', 'POST']);

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher->method('match')->willReturn($matchingResult);

        $collector = new RouterCollector();
        $collector->startup();

        $proxy = new UrlMatcherInterfaceProxy($urlMatcher, $collector);
        $result = $proxy->match($request);

        $this->assertSame($matchingResult, $result);
        $this->assertFalse($result->isSuccess());
    }
}
