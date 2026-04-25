<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\AssetController;
use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use yii\web\NotFoundHttpException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AssetControllerTest extends TestCase
{
    private string $basePath;
    private string $backupFile;
    private bool $createdIndex = false;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_asset_ctrl_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);
        mkdir($this->basePath . '/runtime', 0o777, true);
        mkdir($this->basePath . '/web', 0o777, true);

        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = '8103';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_FILENAME'] = $this->basePath . '/web/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        new \yii\web\Application([
            'id' => 'adp-asset-test',
            'basePath' => $this->basePath,
            'runtimePath' => $this->basePath . '/runtime',
        ]);

        $distDir = FrontendAssets::path();
        if (!is_dir($distDir)) {
            mkdir($distDir, 0o777, true);
        }
        $this->backupFile = sys_get_temp_dir() . '/adp-asset-test-yii2-' . uniqid() . '.bak';
        $indexPath = $distDir . '/index.html';
        if (is_file($indexPath)) {
            copy($indexPath, $this->backupFile);
        } else {
            file_put_contents($indexPath, '<!doctype html><title>t</title>');
            $this->createdIndex = true;
        }
    }

    protected function tearDown(): void
    {
        $distDir = FrontendAssets::path();
        $indexPath = $distDir . '/index.html';
        if (is_file($this->backupFile)) {
            copy($this->backupFile, $indexPath);
            unlink($this->backupFile);
        } elseif ($this->createdIndex) {
            @unlink($indexPath);
        }
        foreach (['bundle.js'] as $rel) {
            $p = $distDir . '/' . $rel;
            if (is_file($p) && str_contains(file_get_contents($p), 'test-fixture-payload')) {
                unlink($p);
            }
        }

        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;
        $this->removeDir($this->basePath);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $dir . '/' . $entry;
            is_dir($full) ? $this->removeDir($full) : @unlink($full);
        }
        @rmdir($dir);
    }

    public function testServesPanelBundleJs(): void
    {
        $distDir = FrontendAssets::path();
        file_put_contents($distDir . '/bundle.js', '// test-fixture-payload yii2');

        $controller = new AssetController('asset', new \yii\base\Module('app-dev-panel'));
        $response = $controller->actionHandle('bundle.js');

        $this->assertSame('application/javascript; charset=utf-8', $response->headers->get('Content-Type'));
        // sendFile() streams via $response->stream — exact handle differs across Yii2
        // versions; assert headers + that streaming was set up rather than reading the body.
        $this->assertNotNull($response->stream);
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    public function testRejectsDirectoryTraversal(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $controller = new AssetController('asset', new \yii\base\Module('app-dev-panel'));
        $controller->actionHandle('../secret.txt');
    }

    public function testReturns404ForMissingFile(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $controller = new AssetController('asset', new \yii\base\Module('app-dev-panel'));
        $controller->actionHandle('does-not-exist.js');
    }
}
