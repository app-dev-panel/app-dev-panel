<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Api;

use AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class YiiApiMiddlewareTest extends TestCase
{
    #[DataProvider('provideInterceptedPaths')]
    public function testInterceptsMatchingPaths(string $path): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process(new ServerRequest('GET', $path), $handler);

        $this->assertSame('intercepted', (string) $response->getBody());
    }

    public static function provideInterceptedPaths(): iterable
    {
        yield 'debug api root' => ['/debug/api'];
        yield 'debug api sub' => ['/debug/api/summary/123'];
        yield 'inspect api root' => ['/inspect/api'];
        yield 'inspect api routes' => ['/inspect/api/routes'];
        yield 'panel root' => ['/debug'];
        yield 'panel sub path' => ['/debug/logs'];
        yield 'panel deep path' => ['/debug/inspector/routes'];
        yield 'panel with trailing slash' => ['/debug/'];
    }

    #[DataProvider('providePassthroughPaths')]
    public function testPassesThroughNonMatchingPaths(string $path): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process(new ServerRequest('GET', $path), $handler);

        $this->assertSame('passthrough', (string) $response->getBody());
    }

    public static function providePassthroughPaths(): iterable
    {
        yield 'homepage' => ['/'];
        yield 'app route' => ['/users'];
        yield 'similar prefix' => ['/debugger'];
        yield 'unrelated path' => ['/admin/dashboard'];
    }

    /**
     * Verifies query params from the URL are passed through to ApiApplication as-is.
     * Yiisoft uses PSR-7 natively, so no conversion or pollution can occur.
     */
    public function testQueryParamsPreservedInPsr7Request(): void
    {
        $capturedRequest = null;
        $middleware = $this->createCapturingMiddleware($capturedRequest);
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/inspect/api/files?path=/src')->withQueryParams(['path' => '/src']);

        $middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('/src', $capturedRequest->getQueryParams()['path'] ?? null);
    }

    /**
     * Verifies multiple query params are passed through correctly.
     */
    public function testMultipleQueryParamsPreserved(): void
    {
        $capturedRequest = null;
        $middleware = $this->createCapturingMiddleware($capturedRequest);
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/inspect/api/table/users?page=2&limit=50')->withQueryParams([
            'page' => '2',
            'limit' => '50',
        ]);

        $middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $params = $capturedRequest->getQueryParams();
        $this->assertSame('2', $params['page']);
        $this->assertSame('50', $params['limit']);
    }

    /**
     * Verifies the service query param for inspector proxy is passed through.
     */
    public function testServiceQueryParamPreserved(): void
    {
        $capturedRequest = null;
        $middleware = $this->createCapturingMiddleware($capturedRequest);
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/inspect/api/files?path=/&service=python-app')->withQueryParams([
            'path' => '/',
            'service' => 'python-app',
        ]);

        $middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $params = $capturedRequest->getQueryParams();
        $this->assertSame('/', $params['path']);
        $this->assertSame('python-app', $params['service']);
    }

    private function createCapturingMiddleware(?ServerRequestInterface &$capturedRequest): YiiApiMiddleware
    {
        $captureController = new class($capturedRequest) {
            /** @var ServerRequestInterface|null */
            private $captured;

            public function __construct(?ServerRequestInterface &$capturedRequest)
            {
                $this->captured = &$capturedRequest;
            }

            public function index(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response(200, [], 'captured');
            }
        };

        $controllerClass = $captureController::class;

        $router = new Router();
        $router->addRoute(new Route('GET', '/inspect/api/{path+}', [$controllerClass, 'index']));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturnCallback(static fn(string $id) => $id === $controllerClass
            ? $captureController
            : throw new \RuntimeException("Not found: {$id}"));

        $factory = new HttpFactory();

        return new YiiApiMiddleware(new ApiApplication($container, $factory, $factory, $router));
    }

    private function createMiddleware(): YiiApiMiddleware
    {
        // Use a minimal ApiApplication with a single catch-all route
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'intercepted');
            }
        };

        $router = new Router();
        // Catch all debug/inspect/panel paths
        $router->addRoute(new Route('GET', '/debug', [$controller::class, 'index']));
        $router->addRoute(new Route('GET', '/debug/{path+}', [$controller::class, 'index']));
        $router->addRoute(new Route('GET', '/inspect/{path+}', [$controller::class, 'index']));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                $controller::class => $controller,
                default => throw new \RuntimeException("Not found: {$id}"),
            });

        $factory = new HttpFactory();

        return new YiiApiMiddleware(new ApiApplication($container, $factory, $factory, $router));
    }

    private function createPassthroughHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'passthrough');
            }
        };
    }
}
