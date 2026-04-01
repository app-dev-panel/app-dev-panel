<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Controller;

use AppDevPanel\Adapter\Symfony\Controller\AdpApiController;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tests for Symfony AdpApiController — verifying PSR-7 conversion and query param handling.
 */
final class AdpApiControllerTest extends TestCase
{
    public function testInvokeReturnsStandardResponse(): void
    {
        $controller = $this->createController();
        $request = Request::create('/inspect/api/files?path=/', 'GET');
        $response = $controller($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Verifies query params from the URL are correctly passed to the PSR-7 request.
     * Symfony separates route params from query params, so no pollution should occur.
     */
    public function testQueryParamsPreservedInPsr7Request(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/inspect/api/files?path=/src', 'GET');
        // Simulate Symfony route param — it goes to $request->attributes, NOT $request->query
        $request->attributes->set('path', 'files');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('/src', $capturedRequest->getQueryParams()['path'] ?? null);
    }

    /**
     * Verifies multiple query params are correctly passed through.
     */
    public function testMultipleQueryParamsPreserved(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/inspect/api/table/users?page=2&limit=50', 'GET');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $params = $capturedRequest->getQueryParams();
        $this->assertSame('2', $params['page']);
        $this->assertSame('50', $params['limit']);
    }

    /**
     * Verifies that empty query string results in empty query params.
     */
    public function testEmptyQueryParamsWhenNoQueryString(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/debug/api', 'GET');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([], $capturedRequest->getQueryParams());
    }

    /**
     * Verifies that the service query param for inspector proxy is correctly forwarded.
     */
    public function testServiceQueryParamPreserved(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/inspect/api/files?path=/&service=python-app', 'GET');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $params = $capturedRequest->getQueryParams();
        $this->assertSame('/', $params['path']);
        $this->assertSame('python-app', $params['service']);
    }

    public function testInvokeReturnsStreamedResponseForSse(): void
    {
        $controller = $this->createController('text/event-stream', 'data: test');
        $request = Request::create('/debug/api/event-stream', 'GET');
        $response = $controller($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokePreservesResponseHeaders(): void
    {
        $controller = $this->createController('application/json', '{}', 201, ['X-Custom' => 'value']);
        $request = Request::create('/inspect/api/files?path=/', 'GET');
        $response = $controller($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    public function testHttpMethodPreserved(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest, 'PUT');

        $request = Request::create('/inspect/api/translations', 'PUT', [], [], [], [], '{"key":"value"}');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('PUT', $capturedRequest->getMethod());
    }

    /**
     * Create a controller that captures the PSR-7 request for inspection.
     */
    private function createCapturingController(
        ?ServerRequestInterface &$capturedRequest,
        string $method = 'GET',
    ): AdpApiController {
        $psr17 = new Psr17Factory();

        $captureController = new class($psr17, $capturedRequest) {
            /** @var ServerRequestInterface|null */
            private $captured;

            public function __construct(
                private readonly Psr17Factory $psr17,
                ?ServerRequestInterface &$capturedRequest,
            ) {
                $this->captured = &$capturedRequest;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return $this->psr17
                    ->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->psr17->createStream('{"data":[]}'));
            }
        };

        $controllerClass = $captureController::class;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturnCallback(static fn(string $id) => $id === $controllerClass
            ? $captureController
            : throw new \RuntimeException("Not found: {$id}"));

        $router = new Router();
        $router->addRoute(new Route($method, '/debug/api', [$controllerClass, 'handle']));
        $router->addRoute(new Route($method, '/debug/api/{path+}', [$controllerClass, 'handle']));
        $router->addRoute(new Route($method, '/inspect/api/{path+}', [$controllerClass, 'handle']));

        return new AdpApiController(new ApiApplication($container, $psr17, $psr17, $router));
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function createController(
        string $contentType = 'application/json',
        string $body = '{"data":[]}',
        int $status = 200,
        array $extraHeaders = [],
    ): AdpApiController {
        $psr17 = new Psr17Factory();

        $testController = new class($psr17, $contentType, $body, $status, $extraHeaders) {
            /**
             * @param array<string, string> $extraHeaders
             */
            public function __construct(
                private readonly Psr17Factory $psr17,
                private readonly string $contentType,
                private readonly string $body,
                private readonly int $status,
                private readonly array $extraHeaders,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = $this->psr17
                    ->createResponse($this->status)
                    ->withHeader('Content-Type', $this->contentType)
                    ->withBody($this->psr17->createStream($this->body));

                foreach ($this->extraHeaders as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response;
            }
        };

        $controllerClass = $testController::class;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturnCallback(static fn(string $id) => $id === $controllerClass
            ? $testController
            : throw new \RuntimeException("Not found: {$id}"));

        $router = new Router();
        $router->addRoute(new Route('GET', '/inspect/api/{path+}', [$controllerClass, 'handle']));
        $router->addRoute(new Route('GET', '/debug/api/{path+}', [$controllerClass, 'handle']));

        return new AdpApiController(new ApiApplication($container, $psr17, $psr17, $router));
    }
}
