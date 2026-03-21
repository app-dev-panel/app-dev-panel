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
