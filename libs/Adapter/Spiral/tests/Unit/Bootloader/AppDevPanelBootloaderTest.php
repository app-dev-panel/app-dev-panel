<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Bootloader;

use AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader;
use AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor\InterceptorStubsBootstrap;
use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\Inspector\Coverage\StoredCoverageReader;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Spiral\Core\Container;

final class AppDevPanelBootloaderTest extends TestCase
{
    private string $storagePath = '';
    private string $previousStoragePath = '';

    public static function setUpBeforeClass(): void
    {
        InterceptorStubsBootstrap::install();
    }

    protected function setUp(): void
    {
        $previous = getenv('APP_DEV_PANEL_STORAGE_PATH');
        $this->previousStoragePath = is_string($previous) ? $previous : '';

        $this->storagePath = sys_get_temp_dir() . '/adp_spiral_bootloader_' . bin2hex(random_bytes(4));
        putenv('APP_DEV_PANEL_STORAGE_PATH=' . $this->storagePath);
    }

    protected function tearDown(): void
    {
        putenv(
            $this->previousStoragePath !== ''
                ? 'APP_DEV_PANEL_STORAGE_PATH=' . $this->previousStoragePath
                : 'APP_DEV_PANEL_STORAGE_PATH',
        );

        if (is_dir($this->storagePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->storagePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->storagePath);
        }
    }

    public function testDeclaresExplicitCodeCoverageControllerFactory(): void
    {
        $singletons = new AppDevPanelBootloader()->defineSingletons();

        self::assertArrayHasKey(CodeCoverageController::class, $singletons);
        self::assertSame(
            [AppDevPanelBootloader::class, 'initCodeCoverageController'],
            $singletons[CodeCoverageController::class],
        );
    }

    public function testCodeCoverageControllerResolvesWithCollectorRepository(): void
    {
        $container = new Container();
        $bootloader = new AppDevPanelBootloader();
        foreach ($bootloader->defineSingletons() as $abstract => $concrete) {
            $container->bindSingleton($abstract, $concrete);
        }

        $controller = $container->get(CodeCoverageController::class);

        self::assertInstanceOf(CodeCoverageController::class, $controller);
        // Without the repository the endpoint answers 501 — the reader is only built when it is injected.
        self::assertInstanceOf(
            StoredCoverageReader::class,
            new ReflectionProperty(CodeCoverageController::class, 'reader')->getValue($controller),
        );
    }
}
