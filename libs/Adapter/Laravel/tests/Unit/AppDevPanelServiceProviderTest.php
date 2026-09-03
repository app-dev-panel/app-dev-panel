<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit;

use AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider;
use AppDevPanel\Api\Inspector\Controller\CodeCoverageController;
use AppDevPanel\Api\Inspector\Coverage\StoredCoverageReader;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class AppDevPanelServiceProviderTest extends TestCase
{
    private string $basePath = '';

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/adp_laravel_provider_' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/storage', 0o777, true);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->basePath);
    }

    public function testRegisterWiresCodeCoverageControllerWithCollectorRepository(): void
    {
        $app = $this->createApplication();

        new AppDevPanelServiceProvider($app)->register();

        self::assertTrue($app->bound(CodeCoverageController::class));
        $controller = $app->make(CodeCoverageController::class);
        self::assertInstanceOf(CodeCoverageController::class, $controller);
        // Without the repository the endpoint answers 501 — the reader is only built when it is injected.
        self::assertInstanceOf(
            StoredCoverageReader::class,
            new ReflectionProperty(CodeCoverageController::class, 'reader')->getValue($controller),
        );
    }

    private function createApplication(): Application
    {
        $app = new Application($this->basePath);
        $app->instance('config', new Repository([
            'app-dev-panel' => [
                'enabled' => true,
                'storage' => ['path' => $this->basePath . '/storage/debug'],
            ],
        ]));

        return $app;
    }
}
