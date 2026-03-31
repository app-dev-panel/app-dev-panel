<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Api;

use AppDevPanel\Adapter\Yiisoft\Api\YiiApiMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
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
        $container->method('get')->willReturnCallback(static fn(string $id) => match ($id) {
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
