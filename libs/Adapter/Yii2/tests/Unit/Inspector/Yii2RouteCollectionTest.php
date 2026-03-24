<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteAdapter;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteCollection;
use AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy;
use PHPUnit\Framework\TestCase;
use yii\web\UrlManager;
use yii\web\UrlRule;

final class Yii2RouteCollectionTest extends TestCase
{
    public function testGetRoutesWithUrlRules(): void
    {
        $urlManager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'posts' => 'post/index',
                'post/<id:\d+>' => 'post/view',
            ],
        ]);

        $collection = new Yii2RouteCollection($urlManager);
        $routes = $collection->getRoutes();

        $this->assertNotEmpty($routes);
        $this->assertContainsOnlyInstancesOf(Yii2RouteAdapter::class, $routes);
    }

    public function testGetRoutesWithEmptyRules(): void
    {
        $urlManager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [],
        ]);

        $collection = new Yii2RouteCollection($urlManager);
        $routes = $collection->getRoutes();

        $this->assertSame([], $routes);
    }

    public function testGetRoutesUnwrapsUrlRuleProxy(): void
    {
        $innerRule = new UrlRule(['pattern' => 'users', 'route' => 'user/index']);
        $recorder = new \AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder();
        $proxy = new UrlRuleProxy($innerRule, $recorder);

        $urlManager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ]);
        $urlManager->rules = [$proxy];

        $collection = new Yii2RouteCollection($urlManager);
        $routes = $collection->getRoutes();

        $this->assertCount(1, $routes);
        $info = $routes[0]->__debugInfo();
        $this->assertSame('users', $info['name']);
    }

    public function testGetRoutesHandlesNonUrlRuleInterface(): void
    {
        $urlManager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ]);

        $customRule = $this->createMock(\yii\web\UrlRuleInterface::class);
        $urlManager->rules = [$customRule];

        $collection = new Yii2RouteCollection($urlManager);
        $routes = $collection->getRoutes();

        $this->assertCount(1, $routes);
        $info = $routes[0]->__debugInfo();
        $this->assertSame([], $info['hosts']);
    }
}
