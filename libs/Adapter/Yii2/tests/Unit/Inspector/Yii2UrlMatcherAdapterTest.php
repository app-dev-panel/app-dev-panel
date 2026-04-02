<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2UrlMatcherAdapter;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use yii\web\Application;
use yii\web\UrlManager;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class Yii2UrlMatcherAdapterTest extends TestCase
{
    private ?Application $originalApp = null;

    protected function setUp(): void
    {
        $this->originalApp = \Yii::$app;

        // Required by yii\web\Application to determine entry script URL in separate processes
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';

        // Boot a minimal Yii 2 web app so createController() works
        new Application([
            'id' => 'test-app',
            'basePath' => __DIR__,
            'controllerNamespace' => 'AppDevPanel\\Adapter\\Yii2\\Tests\\Unit\\Inspector\\Fixture',
        ]);
    }

    protected function tearDown(): void
    {
        \Yii::$app = $this->originalApp;
        unset($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME']);
    }

    public function testMatchReturnsSuccessForKnownRoute(): void
    {
        $urlManager = $this->createUrlManager([
            'about' => 'stub/about',
            '' => 'stub/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/about');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertCount(1, $result->middlewares);
        $this->assertStringContainsString('StubController', $result->middlewares[0]);
        $this->assertStringContainsString('actionAbout', $result->middlewares[0]);
    }

    public function testMatchReturnsFailureForUnknownRoute(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'stub/index',
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
            'POST login' => 'stub/login',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);

        $postRequest = new ServerRequest('POST', '/login');
        $postResult = $adapter->match($postRequest);
        $this->assertTrue($postResult->isSuccess());

        $getRequest = new ServerRequest('GET', '/login');
        $getResult = $adapter->match($getRequest);
        $this->assertFalse($getResult->isSuccess());
    }

    public function testMatchRootRoute(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'stub/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/');
        $result = $adapter->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('StubController', $result->middlewares[0]);
    }

    public function testRouteReturnsSelf(): void
    {
        $urlManager = $this->createUrlManager([
            '' => 'stub/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/');
        $result = $adapter->match($request);

        $this->assertSame($result, $result->route());
    }

    public function testMatchReturnsFailureForNonExistentController(): void
    {
        $urlManager = $this->createUrlManager([
            'missing' => 'nonexistent/index',
        ]);

        $adapter = new Yii2UrlMatcherAdapter($urlManager);
        $request = new ServerRequest('GET', '/missing');
        $result = $adapter->match($request);

        // Route matches URL pattern but controller doesn't exist
        $this->assertFalse($result->isSuccess());
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
