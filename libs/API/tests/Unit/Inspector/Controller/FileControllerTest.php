<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\PathResolverInterface;

final class FileControllerTest extends ControllerTestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/adp-file-test-' . uniqid();
        mkdir($this->fixtureDir, 0o755, true);
        file_put_contents($this->fixtureDir . '/test.txt', 'hello world');
        mkdir($this->fixtureDir . '/subdir', 0o755, true);
        file_put_contents($this->fixtureDir . '/subdir/nested.php', '<?php echo 1;');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->fixtureDir);
    }

    private function createController(): FileController
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($this->fixtureDir);
        $pathResolver->method('getRuntimePath')->willReturn($this->fixtureDir . '/runtime');

        return new FileController($this->createResponseFactory(), $pathResolver);
    }

    public function testListRootDirectory(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);

        $names = array_column($data, 'baseName');
        $this->assertContains('test.txt', $names);
        $this->assertContains('subdir', $names);
    }

    public function testReadFile(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/test.txt']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('hello world', $data['content']);
        $this->assertSame('txt', $data['extension']);
    }

    public function testReadNestedFile(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/subdir/nested.php']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('<?php echo 1;', $data['content']);
        $this->assertSame('php', $data['extension']);
    }

    public function testListSubdirectory(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/subdir']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $names = array_column($data, 'baseName');
        $this->assertContains('nested.php', $names);
    }

    public function testPathNotFound(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/nonexistent.txt']));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testPathTraversalBlocked(): void
    {
        // Create a file outside the root
        $outsideFile = sys_get_temp_dir() . '/adp-outside-' . uniqid() . '.txt';
        file_put_contents($outsideFile, 'secret');

        try {
            $controller = $this->createController();
            // Try to read a path that resolves outside root via symlink
            $symlinkPath = $this->fixtureDir . '/escape';
            symlink(dirname($outsideFile), $symlinkPath);

            $response = $controller->files($this->get(['path' => '/escape/' . basename($outsideFile)]));
            $this->assertSame(403, $response->getStatusCode());
        } finally {
            @unlink($outsideFile);
            @unlink($symlinkPath ?? '');
        }
    }

    public function testReadByClassName(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['class' => self::class]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('content', $data);
        $this->assertStringContainsString('FileControllerTest', $data['content']);
    }

    public function testReadByClassNameWithMethod(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get([
            'class' => self::class,
            'method' => 'testReadByClassNameWithMethod',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('startLine', $data);
        $this->assertArrayHasKey('endLine', $data);
        $this->assertIsInt($data['startLine']);
    }

    public function testFileInfoFields(): void
    {
        $controller = $this->createController();
        $response = $controller->files($this->get(['path' => '/test.txt']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('baseName', $data);
        $this->assertArrayHasKey('extension', $data);
        $this->assertArrayHasKey('size', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('group', $data);
        $this->assertArrayHasKey('mtime', $data);
        $this->assertIsInt($data['mtime']);
        $this->assertArrayHasKey('directory', $data);
        $this->assertArrayHasKey('absolutePath', $data);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
