<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Controller;

use AppDevPanel\Adapter\Laravel\Controller\AdpApiController;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdpApiControllerTest extends TestCase
{
    public function testInvokeReturnsStandardResponse(): void
    {
        $controller = $this->createController('application/json', '{"data":"test"}');
        $request = Request::create('/debug/api/test', 'GET');
        $response = $controller($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"data":"test"}', $response->getContent());
    }

    public function testInvokeReturnsStreamedResponseForSse(): void
    {
        $controller = $this->createController('text/event-stream', 'data: test');
        $request = Request::create('/debug/api/test', 'GET');
        $response = $controller($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokePreservesResponseHeaders(): void
    {
        $controller = $this->createController('application/json', '{}', 201, ['X-Custom' => 'value']);
        $request = Request::create('/debug/api/test', 'GET');
        $response = $controller($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    /**
     * Verifies query params from the URL are correctly passed to the PSR-7 request.
     * Laravel separates route params from query params, so no pollution should occur.
     */
    public function testQueryParamsPreservedInPsr7Request(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/inspect/api/files?path=/src', 'GET');
        // Simulate Laravel route param — it goes to $request->route(), NOT $request->query
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
     * Verifies empty query string results in empty query params.
     */
    public function testEmptyQueryParamsWhenNoQueryString(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/debug/api/test', 'GET');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame([], $capturedRequest->getQueryParams());
    }

    /**
     * Verifies the service query param for inspector proxy is correctly forwarded.
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

    /**
     * Verifies headers from the Laravel request are forwarded to PSR-7.
     */
    public function testRequestHeadersForwardedToPsr7Request(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/debug/api/test', 'GET');
        $request->headers->set('X-Custom-Header', 'custom-value');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('custom-value', $capturedRequest->getHeaderLine('x-custom-header'));
    }

    /**
     * Verifies that request method is correctly forwarded to the PSR-7 request.
     */
    public function testRequestMethodForwardedToPsr7Request(): void
    {
        $capturedRequest = null;
        $controller = $this->createCapturingController($capturedRequest);

        $request = Request::create('/debug/api/test', 'GET');

        $controller($request);

        $this->assertNotNull($capturedRequest);
        $this->assertSame('GET', $capturedRequest->getMethod());
    }

    /**
     * Verifies non-SSE content types produce standard Response.
     */
    public function testNonSseContentTypeReturnsStandardResponse(): void
    {
        $controller = $this->createController('text/html', '<h1>Test</h1>');
        $request = Request::create('/debug/api/test', 'GET');
        $response = $controller($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('<h1>Test</h1>', $response->getContent());
    }

    /**
     * Create a controller that captures the PSR-7 request for inspection.
     */
    private function createCapturingController(?ServerRequestInterface &$capturedRequest): AdpApiController
    {
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

            public function test(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return $this->psr17
                    ->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->psr17->createStream('{"data":[]}'));
            }
        };

        $controllerClass = $captureController::class;

        $container = new class($captureController, $controllerClass) implements ContainerInterface {
            public function __construct(
                private readonly object $controller,
                private readonly string $controllerClass,
            ) {}

            public function get(string $id): mixed
            {
                if ($id === $this->controllerClass) {
                    return $this->controller;
                }
                return null;
            }

            public function has(string $id): bool
            {
                return $id === $this->controllerClass;
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api/test', [$controllerClass, 'test']));
        $router->addRoute(new Route('GET', '/inspect/api/{path+}', [$controllerClass, 'test']));

        $apiApp = new ApiApplication($container, $psr17, $psr17, $router);

        return new AdpApiController($apiApp);
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function createController(
        string $contentType,
        string $body,
        int $status = 200,
        array $extraHeaders = [],
    ): AdpApiController {
        $psr17 = new Psr17Factory();

        // Create a test controller class inline
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

            public function test(ServerRequestInterface $request): ResponseInterface
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

        $container = new class($testController, $controllerClass) implements ContainerInterface {
            public function __construct(
                private readonly object $controller,
                private readonly string $controllerClass,
            ) {}

            public function get(string $id): mixed
            {
                if ($id === $this->controllerClass) {
                    return $this->controller;
                }
                return null;
            }

            public function has(string $id): bool
            {
                return $id === $this->controllerClass;
            }
        };

        $router = new Router();
        $router->addRoute(new Route('GET', '/debug/api/test', [$controllerClass, 'test']));

        $apiApp = new ApiApplication($container, $psr17, $psr17, $router);

        return new AdpApiController($apiApp);
    }
}
