<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2ConfigProvider;
use PHPUnit\Framework\TestCase;
use yii\base\Application;

final class Yii2ConfigProviderTest extends TestCase
{
    public function testGetParamsGroup(): void
    {
        $params = ['appName' => 'Test App', 'debug' => true];

        $app = $this->createMock(Application::class);
        $app->params = $params;

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('params');

        $this->assertSame('Test App', $result['appName']);
        $this->assertTrue($result['debug']);
    }

    public function testGetParametersGroupIsAlias(): void
    {
        $params = ['key' => 'value'];

        $app = $this->createMock(Application::class);
        $app->params = $params;

        $provider = new Yii2ConfigProvider($app);

        $this->assertSame($provider->get('params'), $provider->get('parameters'));
    }

    public function testGetServicesGroup(): void
    {
        $components = [
            'db' => ['class' => 'yii\\db\\Connection'],
            'log' => ['class' => 'yii\\log\\Dispatcher'],
            'cache' => 'yii\\caching\\FileCache',
        ];

        $app = $this->createMock(Application::class);
        $app->method('getComponents')->willReturn($components);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('services');

        $this->assertSame('yii\\db\\Connection', $result['db']);
        $this->assertSame('yii\\log\\Dispatcher', $result['log']);
        $this->assertSame('yii\\caching\\FileCache', $result['cache']);
    }

    public function testGetDiGroupIsServicesAlias(): void
    {
        $components = ['test' => ['class' => 'TestClass']];

        $app = $this->createMock(Application::class);
        $app->method('getComponents')->willReturn($components);

        $provider = new Yii2ConfigProvider($app);

        $this->assertSame($provider->get('services'), $provider->get('di'));
    }

    public function testGetModulesGroup(): void
    {
        $modules = [
            'debug' => ['class' => 'yii\\debug\\Module'],
            'gii' => 'yii\\gii\\Module',
        ];

        $app = $this->createMock(Application::class);
        $app->method('getModules')->willReturn($modules);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('modules');

        $this->assertSame('yii\\debug\\Module', $result['debug']);
        $this->assertSame('yii\\gii\\Module', $result['gii']);
    }

    public function testGetEventsGroup(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('getBehaviors')->willReturn([]);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('events');

        $this->assertSame([], $result);
    }

    public function testGetUnknownGroupReturnsEmpty(): void
    {
        $app = $this->createMock(Application::class);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('nonexistent');

        $this->assertSame([], $result);
    }

    public function testComponentsAreSorted(): void
    {
        $components = [
            'zebra' => ['class' => 'ZebraClass'],
            'alpha' => ['class' => 'AlphaClass'],
            'middle' => ['class' => 'MiddleClass'],
        ];

        $app = $this->createMock(Application::class);
        $app->method('getComponents')->willReturn($components);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('services');

        $keys = array_keys($result);
        $this->assertSame('alpha', $keys[0]);
        $this->assertSame('middle', $keys[1]);
        $this->assertSame('zebra', $keys[2]);
    }

    public function testComponentWithObjectDefinition(): void
    {
        $obj = new \stdClass();
        $components = [
            'custom' => $obj,
        ];

        $app = $this->createMock(Application::class);
        $app->method('getComponents')->willReturn($components);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('services');

        $this->assertSame(\stdClass::class, $result['custom']);
    }

    public function testComponentWithNonClassDefinition(): void
    {
        $components = [
            'custom' => 42,
        ];

        $app = $this->createMock(Application::class);
        $app->method('getComponents')->willReturn($components);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('services');

        $this->assertSame('int', $result['custom']);
    }

    public function testGetEventsWebGroupIsAlias(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('getBehaviors')->willReturn([]);

        $provider = new Yii2ConfigProvider($app);

        $this->assertSame($provider->get('events'), $provider->get('events-web'));
    }

    public function testGetEventsWithBehaviors(): void
    {
        $behavior = new \yii\base\Behavior();

        $app = $this->createMock(Application::class);
        $app->method('getBehaviors')->willReturn(['myBehavior' => $behavior]);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('events');

        $this->assertArrayHasKey('behavior:myBehavior', $result);
        $this->assertSame([\yii\base\Behavior::class], $result['behavior:myBehavior']);
    }

    public function testGetEventsWithClassLevelHandlers(): void
    {
        // Register a class-level event handler
        \yii\base\Event::on(\yii\web\Application::class, 'testEvent', static function (): void {});

        $app = $this->createMock(Application::class);
        $app->method('getBehaviors')->willReturn([]);

        $provider = new Yii2ConfigProvider($app);
        $result = $provider->get('events');

        $this->assertNotEmpty($result);

        // Clean up
        \yii\base\Event::offAll();
    }

    public function testDescribeHandlerWithStringHandler(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $this->assertSame('myFunction', $method->invoke(null, 'myFunction'));
    }

    public function testDescribeHandlerWithArrayHandler(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, [new \stdClass(), 'doSomething']);
        $this->assertSame('stdClass::doSomething', $result);
    }

    public function testDescribeHandlerWithClassStringArrayHandler(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, ['SomeClass', 'staticMethod']);
        $this->assertSame('SomeClass::staticMethod', $result);
    }

    public function testDescribeHandlerWithClosure(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, static function (): void {});
        $this->assertIsArray($result);
        $this->assertTrue($result['__closure']);
        $this->assertStringContainsString('static function', $result['source']);
        $this->assertStringContainsString('void', $result['source']);
        $this->assertSame(__FILE__, $result['file']);
        $this->assertIsInt($result['startLine']);
        $this->assertIsInt($result['endLine']);
    }

    public function testDescribeHandlerWithClosureBody(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, static function (object $event): string {
            return $event::class;
        });
        $this->assertIsArray($result);
        $this->assertTrue($result['__closure']);
        $this->assertStringContainsString('static function', $result['source']);
        $this->assertStringContainsString('object $event', $result['source']);
        $this->assertStringContainsString('return $event::class', $result['source']);
        $this->assertSame(__FILE__, $result['file']);
    }

    public function testDescribeHandlerWithArrowFunction(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, static fn(object $e): string => $e::class);
        $this->assertIsArray($result);
        $this->assertTrue($result['__closure']);
        $this->assertStringContainsString('fn', $result['source']);
        $this->assertStringContainsString('$e', $result['source']);
    }

    public function testDescribeHandlerWithInvokableObject(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $invokable = new class {
            public function __invoke(): void {}
        };

        $result = $method->invoke(null, $invokable);
        $this->assertStringEndsWith('::__invoke', $result);
    }

    public function testDescribeHandlerWithOtherType(): void
    {
        $method = new \ReflectionMethod(Yii2ConfigProvider::class, 'describeHandler');

        $result = $method->invoke(null, 42);
        $this->assertSame('int', $result);
    }
}
