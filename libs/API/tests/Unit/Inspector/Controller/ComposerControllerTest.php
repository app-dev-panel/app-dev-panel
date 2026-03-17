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
        mkdir($this->fixtureDir, 0755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->fixtureDir . '/composer.json');
        @unlink($this->fixtureDir . '/composer.lock');
        @rmdir($this->fixtureDir);
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
}
