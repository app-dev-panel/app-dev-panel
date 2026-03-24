<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\PathResolverInterface;

final class CodeCoverageControllerTest extends ControllerTestCase
{
    private function createPathResolver(): PathResolverInterface
    {
        $resolver = $this->createMock(PathResolverInterface::class);
        $resolver->method('getRootPath')->willReturn(dirname(__DIR__, 5));
        $resolver->method('getRuntimePath')->willReturn(sys_get_temp_dir());

        return $resolver;
    }

    private function createController(): CodeCoverageController
    {
        return new CodeCoverageController(
            $this->createResponseFactory(),
            $this->createPathResolver(),
            excludePaths: ['vendor'],
        );
    }

    public function testIndex(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());

        $data = $this->responseData($response);
        $this->assertArrayHasKey('files', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('totalFiles', $data['summary']);
        $this->assertArrayHasKey('coveredLines', $data['summary']);
        $this->assertArrayHasKey('executableLines', $data['summary']);
        $this->assertArrayHasKey('percentage', $data['summary']);
    }

    public function testIndexDriverField(): void
    {
        $controller = $this->createController();
        $response = $controller->index($this->get());

        $data = $this->responseData($response);
        $this->assertArrayHasKey('driver', $data);
        $this->assertContains($data['driver'], [null, 'pcov', 'xdebug']);
    }

    public function testFileMissingPath(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get());

        $this->assertSame(400, $response->getStatusCode());

        $data = $this->responseData($response);
        $this->assertSame('Missing required parameter: path', $data['message']);
    }

    public function testFileNotFound(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => '/nonexistent/file.php']));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testFileSuccess(): void
    {
        $controller = $this->createController();
        $response = $controller->file($this->get(['path' => __FILE__]));

        $this->assertSame(200, $response->getStatusCode());

        $data = $this->responseData($response);
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('lines', $data);
        $this->assertGreaterThan(0, $data['lines']);
    }
}
