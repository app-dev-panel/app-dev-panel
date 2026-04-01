<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\AdpApiController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use yii\web\Application;

/**
 * Tests for AdpApiController — verifying PSR-7 request conversion
 * preserves the original query string and is not polluted by Yii 2 URL rule parameters.
 */
final class AdpApiControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_api_ctrl_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);
        mkdir($this->basePath . '/runtime', 0o777, true);
        mkdir($this->basePath . '/web', 0o777, true);

        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = '8103';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        unset(
            $_SERVER['SERVER_NAME'],
            $_SERVER['SERVER_PORT'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['QUERY_STRING'],
            $_SERVER['HTTP_HOST'],
        );

        if (is_dir($this->basePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->basePath);
        }
    }

    /**
     * Verifies that query params in the PSR-7 request come from the actual URL query string,
     * not from Yii 2 URL rule parameters.
     *
     * Regression test: Yii 2 URL rules like `inspect/api/<path:.*>` inject a `path` parameter
     * into getQueryParams(), overriding the original `?path=/` from the client.
     */
    public function testQueryParamsNotPollutedByUrlRuleParameters(): void
    {
        $_SERVER['REQUEST_URI'] = '/inspect/api/files?path=/';
        $_SERVER['QUERY_STRING'] = 'path=/';
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8103';

        $app = $this->createWebApp();
        // Simulate Yii 2 URL rule parameter pollution: the route `inspect/api/<path:.*>`
        // would add `path=files` to query params, overriding the original `path=/`.
        $app->getRequest()->setQueryParams(['path' => 'files']);

        $psrRequest = $this->callConvertYiiRequestToPsr7($app);

        // The PSR-7 query params must reflect the URL query string (?path=/), not the URL rule match (path=files)
        $this->assertSame('/', $psrRequest->getQueryParams()['path'] ?? null);
    }

    /**
     * Verifies that requests without query params work correctly.
     */
    public function testQueryParamsEmptyWhenNoQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/debug/api';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8103';

        $app = $this->createWebApp();

        $psrRequest = $this->callConvertYiiRequestToPsr7($app);

        $this->assertSame([], $psrRequest->getQueryParams());
    }

    /**
     * Verifies multiple query params are correctly preserved.
     */
    public function testMultipleQueryParamsPreserved(): void
    {
        $_SERVER['REQUEST_URI'] = '/inspect/api/table/users?page=2&limit=50';
        $_SERVER['QUERY_STRING'] = 'page=2&limit=50';
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8103';

        $app = $this->createWebApp();
        // Simulate URL rule pollution adding 'path' param
        $app->getRequest()->setQueryParams(['path' => 'table/users', 'page' => '2', 'limit' => '50']);

        $psrRequest = $this->callConvertYiiRequestToPsr7($app);

        $params = $psrRequest->getQueryParams();
        $this->assertSame('2', $params['page']);
        $this->assertSame('50', $params['limit']);
        // The `path` key should NOT be present since it's not in the real query string
        $this->assertArrayNotHasKey('path', $params);
    }

    /**
     * Verifies that the service query param for inspector proxy is preserved alongside path.
     */
    public function testServiceAndPathQueryParamsPreserved(): void
    {
        $_SERVER['REQUEST_URI'] = '/inspect/api/files?path=/src&service=python-app';
        $_SERVER['QUERY_STRING'] = 'path=/src&service=python-app';
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8103';

        $app = $this->createWebApp();
        // Simulate URL rule pollution: replaces actual path=/ with route match path=files
        $app->getRequest()->setQueryParams(['path' => 'files', 'service' => 'python-app']);

        $psrRequest = $this->callConvertYiiRequestToPsr7($app);

        $params = $psrRequest->getQueryParams();
        $this->assertSame('/src', $params['path']);
        $this->assertSame('python-app', $params['service']);
    }

    /**
     * Verifies HTTP method is correctly transferred to PSR-7 request.
     */
    public function testHttpMethodPreserved(): void
    {
        $_SERVER['REQUEST_URI'] = '/inspect/api/translations';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8103';

        $app = $this->createWebApp();

        $psrRequest = $this->callConvertYiiRequestToPsr7($app);

        $this->assertSame('PUT', $psrRequest->getMethod());
    }

    /**
     * Use reflection to call the private convertYiiRequestToPsr7 method directly.
     */
    private function callConvertYiiRequestToPsr7(Application $app): ServerRequestInterface
    {
        $controller = new AdpApiController('adp-api', $app);

        $method = new \ReflectionMethod($controller, 'convertYiiRequestToPsr7');
        $method->setAccessible(true);

        return $method->invoke($controller, $app->getRequest());
    }

    private function createWebApp(): Application
    {
        return new Application([
            'id' => 'test',
            'basePath' => $this->basePath,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'test-key',
                    'enableCsrfValidation' => false,
                ],
            ],
        ]);
    }
}
