<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiApplicationTest extends TestCase
{
    public function testHandleMatchedRoute(): void
    {
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}');
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api', [$controller::class, 'index']));

        $container = $this->createContainer([$controller::class => $controller]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug/api'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleNotFoundReturns404(): void
    {
        $router = new Router();
        $container = $this->createContainer();
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/nonexistent'));

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertSame('Not found.', $body['error']);
    }

    public function testHandleOptionsOnUnknownPathReturns204(): void
    {
        $router = new Router();
        $container = $this->createContainer();
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('OPTIONS', '/any/path'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testHandleRouteWithParameters(): void
    {
        $capturedRequest = null;
        $controller = new class {
            public ?ServerRequestInterface $captured = null;

            public function view(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response(200, [], '{"id":"' . $request->getAttribute('id') . '"}');
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api/view/{id}', [$controller::class, 'view']));

        $container = $this->createContainer([$controller::class => $controller]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug/api/view/abc123'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $controller->captured->getAttribute('id'));
    }

    public function testTrailingSlashNormalization(): void
    {
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api', [$controller::class, 'index']));

        $container = $this->createContainer([$controller::class => $controller]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug/api/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetRouter(): void
    {
        $router = new Router();
        $container = $this->createContainer();
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $this->assertSame($router, $app->getRouter());
    }

    public function testDefaultRouterRegistersApiRoutes(): void
    {
        $container = $this->createContainer();
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory());

        $routes = $app->getRouter()->getRoutes();
        $this->assertNotEmpty($routes);
    }

    public function testPanelRouteSkipsResponseDataWrapper(): void
    {
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['Content-Type' => 'text/html'], '<html>panel</html>');
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug', [$controller::class, 'index']));

        $container = $this->createContainer([$controller::class => $controller]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug'));

        // Panel routes should NOT be wrapped in {data, success, error} JSON envelope
        $body = (string) $response->getBody();
        $this->assertSame('<html>panel</html>', $body);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPanelSubPathSkipsResponseDataWrapper(): void
    {
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['Content-Type' => 'text/html'], '<html>panel</html>');
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/logs', [$controller::class, 'index']));

        $container = $this->createContainer([$controller::class => $controller]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug/logs'));

        $body = (string) $response->getBody();
        $this->assertSame('<html>panel</html>', $body);
    }

    public function testDebugApiRouteStillWrapsResponse(): void
    {
        $controller = new class {
            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['Content-Type' => 'application/json'], '[]');
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api', [$controller::class, 'index']));

        $wrapper = new \AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper(
            new \AppDevPanel\Api\Http\JsonResponseFactory(new HttpFactory(), new HttpFactory()),
        );
        $container = $this->createContainer([
            $controller::class => $controller,
            \AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper::class => $wrapper,
        ]);
        $app = new ApiApplication($container, new HttpFactory(), new HttpFactory(), $router);

        $response = $app->handle(new ServerRequest('GET', '/debug/api'));

        // API routes SHOULD be wrapped
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('success', $body);
    }

    private function createContainer(array $services = []): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn(string $id) => array_key_exists($id, $services));
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($services) {
                return $services[$id] ?? throw new \RuntimeException("Not found: {$id}");
            });
        return $container;
    }
}
