<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2UrlMatcherAdapter;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use yii\web\UrlManager;

final class Yii2UrlMatcherAdapterTest extends TestCase
{
    public function testMatchReturnsSuccessForKnownRoute(): void
    {
        $urlManager = $this->createUrlManager([
            'about' => 'site/about',
            '' => 'site/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/about');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['site/about'], $result->middlewares);
    }

    public function testMatchReturnsFailureForUnknownRoute(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'site/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/nonexistent');
        $result = $adapter->match($request);

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->middlewares);
    }

    public function testMatchRespectsHttpMethod(): void
    {
        $urlManager = $this->createUrlManager([
            'POST login' => 'auth/login',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);

        $postRequest = new ServerRequest('POST', '/login');
        $postResult = $adapter->match($postRequest);
        $this->assertTrue($postResult->isSuccess());

        $getRequest = new ServerRequest('GET', '/login');
        $getResult = $adapter->match($getRequest);
        $this->assertFalse($getResult->isSuccess());
    }

    public function testMatchWithParameterizedRoute(): void
    {
        $urlManager = $this->createUrlManager([
            'user/<id:\d+>' => 'user/view',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/user/42');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['user/view'], $result->middlewares);
    }

    public function testRouteReturnsSelf(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'site/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/');
        $result = $adapter->match($request);

        $this->assertSame($result, $result->route());
    }

    public function testMatchRootRoute(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'site/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['site/index'], $result->middlewares);
    }

    private function createUrlManager(array $rules): UrlManager
    {
        $urlManager = new UrlManager();
        $urlManager->enablePrettyUrl = true;
        $urlManager->showScriptName = false;
        $urlManager->enableStrictParsing = true;
        $urlManager->rules = $rules;
        $urlManager->init();

        return $urlManager;
    }
}
