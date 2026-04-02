<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit;

use AppDevPanel\Adapter\Yii2\Module;
use PHPUnit\Framework\TestCase;

/**
 * Tests that Module::init() registers the namespace alias
 * so Yii 2 can resolve the controller path from controllerNamespace.
 *
 * Without the alias, running `./yii` (help/list) throws:
 *   Invalid path alias: @AppDevPanel/Adapter/Yii2/Controller
 */
final class ModuleInitTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_init_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

        if (!is_dir($this->storagePath . '/runtime')) {
            mkdir($this->storagePath . '/runtime', 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        // Clean up alias
        \Yii::setAlias('@AppDevPanel', null);

        $this->removeDirectory($this->storagePath);
    }

    public function testInitRegistersNamespaceAlias(): void
    {
        new Module('app-dev-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        $alias = \Yii::getAlias('@AppDevPanel/Adapter/Yii2', false);
        $this->assertNotFalse($alias, 'Alias @AppDevPanel/Adapter/Yii2 must be registered after init()');
    }

    public function testControllerPathIsResolvable(): void
    {
        $module = new Module('app-dev-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        // getControllerPath() internally resolves @AppDevPanel/Adapter/Yii2/Controller.
        // Without the alias this throws InvalidArgumentException.
        $path = $module->getControllerPath();

        $this->assertStringEndsWith('Controller', $path);
        $this->assertDirectoryExists($path);
    }

    public function testAliasPointsToSourceDirectory(): void
    {
        new Module('app-dev-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        $alias = \Yii::getAlias('@AppDevPanel/Adapter/Yii2');
        $srcDir = dirname(new \ReflectionClass(Module::class)->getFileName());

        $this->assertSame(
            realpath($srcDir),
            realpath($alias),
            'Alias must point to the src/ directory of the Yii2 adapter',
        );
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
