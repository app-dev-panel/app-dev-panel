<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Api;

use AppDevPanel\Adapter\Yii3\Api\AdpAssetMiddleware;
use AppDevPanel\FrontendAssets\FrontendAssets;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdpAssetMiddlewareTest extends TestCase
{
    private bool $createdIndex = false;

    protected function setUp(): void
    {
        $distDir = FrontendAssets::path();
        if (!is_dir($distDir)) {
            mkdir($distDir, 0o777, true);
        }
        $indexPath = $distDir . '/index.html';
        if (!is_file($indexPath)) {
            file_put_contents($indexPath, '<!doctype html><title>t</title>');
            $this->createdIndex = true;
        }
    }

    protected function tearDown(): void
    {
        $distDir = FrontendAssets::path();
        foreach (['bundle.js'] as $rel) {
            $p = $distDir . '/' . $rel;
            if (is_file($p) && str_contains((string) file_get_contents($p), 'test-fixture-payload-yii3')) {
                @unlink($p);
            }
        }
        if ($this->createdIndex) {
            @unlink($distDir . '/index.html');
        }
    }

    public function testServesAssetFromFrontendAssetsPackage(): void
    {
        file_put_contents(FrontendAssets::path() . '/bundle.js', '// test-fixture-payload-yii3');

        $response = $this->createMiddleware()->process(
            new ServerRequest('GET', '/debug-assets/bundle.js'),
            $this->createPassthroughHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('test-fixture-payload-yii3', (string) $response->getBody());
    }

    public function testTraversalReturns404(): void
    {
        $response = $this->createMiddleware()->process(
            new ServerRequest('GET', '/debug-assets/../secret.txt'),
            $this->createPassthroughHandler(),
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testNonAssetPathFallsThrough(): void
    {
        $response = $this->createMiddleware()->process(
            new ServerRequest('GET', '/some/other/path'),
            $this->createPassthroughHandler(),
        );

        $this->assertSame('passthrough', (string) $response->getBody());
    }

    private function createMiddleware(): AdpAssetMiddleware
    {
        $factory = new HttpFactory();
        return new AdpAssetMiddleware($factory, $factory);
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
