<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\DebugResetController;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

final class DebugResetControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_reset_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);

        new Application([
            'id' => 'test',
            'basePath' => $this->basePath,
        ]);
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        if (is_dir($this->basePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->basePath);
        }
    }

    public function testActionIndexClearsStorage(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('clear');

        $debugger = new Debugger(new DebuggerIdGenerator(), $storage, []);

        $controller = new DebugResetController('debug-reset', \Yii::$app, $storage, $debugger);

        ob_start();
        $result = $controller->actionIndex();
        ob_end_clean();

        $this->assertSame(ExitCode::OK, $result);
    }
}
