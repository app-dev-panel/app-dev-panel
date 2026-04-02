<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\PathResolverInterface;
use Exception;
use InvalidArgumentException;

final class ComposerControllerTest extends ControllerTestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/adp-composer-test-' . uniqid();
        mkdir($this->fixtureDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixtureDir)) {
            $this->removeDirectory($this->fixtureDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createController(): ComposerController
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($this->fixtureDir);
        $pathResolver->method('getRuntimePath')->willReturn($this->fixtureDir . '/runtime');

        return new ComposerController($this->createResponseFactory(), $pathResolver);
    }

    public function testIndexWithJsonAndLock(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode([
            'name' => 'test/app',
            'require' => ['php' => '>=8.4'],
        ]));
        file_put_contents($this->fixtureDir . '/composer.lock', json_encode([
            'packages' => [],
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('test/app', $data['json']['name']);
        $this->assertArrayHasKey('lock', $data);
        $this->assertIsArray($data['lock']);
    }

    public function testIndexWithJsonOnly(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode([
            'name' => 'test/no-lock',
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertNull($data['lock']);
    }

    public function testIndexNoComposerJson(): void
    {
        $controller = $this->createController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('composer.json');
        $controller->index($this->get());
    }

    public function testInspectMissingPackage(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('package');
        $controller->inspect($this->get());
    }

    public function testRequireMissingPackage(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('package');
        $controller->require($this->post([]));
    }

    public function testIndexWithInvalidJson(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', '{not valid json}');

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->index($this->get());
    }

    public function testIndexWithInvalidLockJson(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['name' => 'test/app']));
        file_put_contents($this->fixtureDir . '/composer.lock', 'broken json');

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->index($this->get());
    }

    public function testIndexWithComplexComposerJson(): void
    {
        $composerJson = [
            'name' => 'test/complex',
            'require' => ['php' => '^8.4', 'vendor/a' => '^1.0'],
            'require-dev' => ['phpunit/phpunit' => '^11.0'],
            'autoload' => ['psr-4' => ['App\\' => 'src/']],
            'scripts' => ['test' => 'phpunit'],
        ];
        file_put_contents($this->fixtureDir . '/composer.json', json_encode($composerJson));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertSame('test/complex', $data['json']['name']);
        $this->assertArrayHasKey('require', $data['json']);
        $this->assertArrayHasKey('require-dev', $data['json']);
        $this->assertArrayHasKey('scripts', $data['json']);
        $this->assertNull($data['lock']);
    }

    public function testIndexWithLockContainingPackages(): void
    {
        file_put_contents($this->fixtureDir . '/composer.json', json_encode(['name' => 'test/app']));
        file_put_contents($this->fixtureDir . '/composer.lock', json_encode([
            'packages' => [
                ['name' => 'vendor/lib', 'version' => '1.0.0'],
                ['name' => 'vendor/other', 'version' => '2.3.4'],
            ],
            'packages-dev' => [
                ['name' => 'dev/tool', 'version' => '0.9.0'],
            ],
        ]));

        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertCount(2, $data['lock']['packages']);
        $this->assertSame('vendor/lib', $data['lock']['packages'][0]['name']);
        $this->assertCount(1, $data['lock']['packages-dev']);
    }

    public function testInspectWithExistingPackage(): void
    {
        // Use the real project root so `composer show` works
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 6));
        $pathResolver->method('getRuntimePath')->willReturn(dirname(__DIR__, 6) . '/runtime');

        $controller = new ComposerController($this->createResponseFactory(), $pathResolver);
        $response = $controller->inspect($this->get(['package' => 'phpunit/phpunit']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testInspectWithNonexistentPackage(): void
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 6));
        $pathResolver->method('getRuntimePath')->willReturn(dirname(__DIR__, 6) . '/runtime');

        $controller = new ComposerController($this->createResponseFactory(), $pathResolver);
        $response = $controller->inspect($this->get(['package' => 'nonexistent/pkg-zzz-999']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('status', $data);
        // Nonexistent package: command fails
        $this->assertNotSame('ok', $data['status']);
    }

    public function testRequireWithInvalidBodyJson(): void
    {
        $request = new \Nyholm\Psr7\ServerRequest('POST', '/test');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\Nyholm\Psr7\Stream::create('{not valid json}'));

        $controller = $this->createController();

        $this->expectException(\JsonException::class);
        $controller->require($request);
    }

    public function testRequireWithNullPackageInBody(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('package');
        $controller->require($this->post(['package' => null]));
    }

    public function testRequireWithEmptyBody(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->require($this->post([]));
    }

    public function testRequireWithPackageReturnsResult(): void
    {
        // Use the real project root so composer binary is available
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 6));
        $pathResolver->method('getRuntimePath')->willReturn(dirname(__DIR__, 6) . '/runtime');

        $controller = new ComposerController($this->createResponseFactory(), $pathResolver);

        // Dry-run: require a non-existent package — will error but exercises the method
        $response = $controller->require($this->post([
            'package' => 'nonexistent/package-zzz-999',
            'version' => '1.0.0',
            'isDev' => false,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRequireWithDevFlag(): void
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 6));
        $pathResolver->method('getRuntimePath')->willReturn(dirname(__DIR__, 6) . '/runtime');

        $controller = new ComposerController($this->createResponseFactory(), $pathResolver);

        $response = $controller->require($this->post([
            'package' => 'nonexistent/package-zzz-999',
            'isDev' => true,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('status', $data);
    }
}
