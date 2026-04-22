<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Api;

use AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ToolbarMiddlewareTest extends TestCase
{
    public function testInjectsToolbarIntoHtmlResponse(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler(
            '<html><body><h1>Hello</h1></body></html>',
            'text/html; charset=UTF-8',
        );

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost:8101/'), $handler);

        $body = (string) $response->getBody();
        $this->assertStringContainsString('app-dev-toolbar', $body);
        $this->assertStringContainsString('/assets/toolbar/bundle.js', $body);
        $this->assertStringContainsString('/assets/toolbar/bundle.css', $body);
        $this->assertStringContainsString("baseUrl: 'http://localhost:8101'", $body);
    }

    public function testSkipsInjectionWhenDisabled(): void
    {
        $middleware = $this->createMiddleware(toolbarEnabled: false);
        $handler = $this->createHandler('<html><body>Hello</body></html>', 'text/html');

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/'), $handler);

        $this->assertStringNotContainsString('app-dev-toolbar', (string) $response->getBody());
    }

    public function testSkipsInjectionForJsonResponse(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler('{"ok":true}', 'application/json');

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/api'), $handler);

        $this->assertSame('{"ok":true}', (string) $response->getBody());
    }

    public function testSkipsInjectionForPanelRequest(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler(
            '<html><body><div id="root"></div></body></html>',
            'text/html',
        );

        $response = $middleware->process(
            new ServerRequest('GET', 'http://localhost:8101/debug?toolbar=0'),
            $handler,
        );

        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('app-dev-toolbar', $body);
        $this->assertStringContainsString('<div id="root"></div>', $body);
    }

    public function testSkipsInjectionForEmptyBody(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler('', 'text/html');

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/'), $handler);

        $this->assertSame('', (string) $response->getBody());
    }

    public function testSkipsInjectionWhenNoContentTypeHeader(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler('<html><body>Hello</body></html>', null);

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/'), $handler);

        $this->assertStringNotContainsString('app-dev-toolbar', (string) $response->getBody());
    }

    public function testExtractsBackendUrlFromRequest(): void
    {
        $middleware = $this->createMiddleware();
        $handler = $this->createHandler('<html><body></body></html>', 'text/html');

        $response = $middleware->process(
            new ServerRequest('GET', 'https://myapp.local:9090/page'),
            $handler,
        );

        $body = (string) $response->getBody();
        $this->assertStringContainsString("baseUrl: 'https://myapp.local:9090'", $body);
    }

    public function testPreservesResponseHeaders(): void
    {
        $middleware = $this->createMiddleware();
        $inner = new Response(200, [
            'Content-Type' => 'text/html',
            'X-Custom' => 'value',
        ], '<html><body>Hi</body></html>');
        $handler = $this->createHandlerWithResponse($inner);

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/'), $handler);

        $this->assertSame(['value'], $response->getHeader('X-Custom'));
        $this->assertStringContainsString('app-dev-toolbar', (string) $response->getBody());
    }

    public function testIncludesDebugIdInInjectedToolbar(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $debugId = $idGenerator->getId();

        $injector = new ToolbarInjector(
            new PanelConfig('/assets'),
            new ToolbarConfig(enabled: true),
        );
        $factory = new HttpFactory();

        $middleware = new ToolbarMiddleware($injector, $idGenerator, $factory);
        $handler = $this->createHandler('<html><body></body></html>', 'text/html');

        $response = $middleware->process(new ServerRequest('GET', 'http://localhost/'), $handler);

        $body = (string) $response->getBody();
        $this->assertStringContainsString("debugId: '{$debugId}'", $body);
    }

    private function createMiddleware(bool $toolbarEnabled = true): ToolbarMiddleware
    {
        $injector = new ToolbarInjector(
            new PanelConfig('/assets'),
            new ToolbarConfig(enabled: $toolbarEnabled),
        );
        $idGenerator = new DebuggerIdGenerator();
        $factory = new HttpFactory();

        return new ToolbarMiddleware($injector, $idGenerator, $factory);
    }

    private function createHandler(string $body, ?string $contentType): RequestHandlerInterface
    {
        $headers = $contentType !== null ? ['Content-Type' => $contentType] : [];
        $response = new Response(200, $headers, $body);

        return $this->createHandlerWithResponse($response);
    }

    private function createHandlerWithResponse(ResponseInterface $response): RequestHandlerInterface
    {
        return new class($response) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }
}
