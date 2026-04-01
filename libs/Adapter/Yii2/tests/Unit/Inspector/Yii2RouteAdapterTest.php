<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteAdapter;
use PHPUnit\Framework\TestCase;
use yii\web\UrlRule;

final class Yii2RouteAdapterTest extends TestCase
{
    public function testDebugInfoWithUrlRule(): void
    {
        $rule = new UrlRule([
            'pattern' => 'user/<id:\d+>',
            'route' => 'user/view',
            'verb' => ['GET', 'HEAD'],
            'host' => 'example.com',
            'defaults' => ['page' => '1'],
        ]);

        $adapter = new Yii2RouteAdapter($rule);
        $info = $adapter->__debugInfo();

        $this->assertSame('user/<id:\d+>', $info['name']);
        $this->assertSame(['example.com'], $info['hosts']);
        $this->assertSame('user/<id:\d+>', $info['pattern']);
        $this->assertSame(['GET', 'HEAD'], $info['methods']);
        $this->assertSame(['page' => '1'], $info['defaults']);
        $this->assertSame(0, $info['override']);
        $this->assertSame(['user/view'], $info['middlewares']);
    }

    public function testDebugInfoWithResolvedAction(): void
    {
        $rule = new UrlRule([
            'pattern' => 'user/<id:\d+>',
            'route' => 'user/view',
            'verb' => ['GET'],
        ]);

        $adapter = new Yii2RouteAdapter($rule, '', ['app\\controllers\\UserController', 'actionView']);
        $info = $adapter->__debugInfo();

        $this->assertSame([['app\\controllers\\UserController', 'actionView']], $info['middlewares']);
    }

    public function testDebugInfoWithNullRule(): void
    {
        $adapter = new Yii2RouteAdapter(null, 'yii\rest\UrlRule');
        $info = $adapter->__debugInfo();

        $this->assertSame('yii\rest\UrlRule', $info['name']);
        $this->assertSame([], $info['hosts']);
        $this->assertSame('yii\rest\UrlRule', $info['pattern']);
        $this->assertSame([], $info['methods']);
        $this->assertSame([], $info['defaults']);
        $this->assertSame(0, $info['override']);
        $this->assertSame([], $info['middlewares']);
    }

    public function testDebugInfoWithRuleNoHost(): void
    {
        $rule = new UrlRule([
            'pattern' => 'posts',
            'route' => 'post/index',
        ]);

        $adapter = new Yii2RouteAdapter($rule);
        $info = $adapter->__debugInfo();

        $this->assertSame([], $info['hosts']);
    }

    public function testDebugInfoWithRuleNoVerb(): void
    {
        $rule = new UrlRule([
            'pattern' => 'posts',
            'route' => 'post/index',
        ]);

        $adapter = new Yii2RouteAdapter($rule);
        $info = $adapter->__debugInfo();

        $this->assertSame([], $info['methods']);
    }

    public function testDebugInfoWithUnresolvedActionFallsBackToRouteString(): void
    {
        $rule = new UrlRule([
            'pattern' => 'posts',
            'route' => 'post/index',
        ]);

        $adapter = new Yii2RouteAdapter($rule, '', null);
        $info = $adapter->__debugInfo();

        $this->assertSame(['post/index'], $info['middlewares']);
    }
}
