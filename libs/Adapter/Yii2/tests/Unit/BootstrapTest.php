<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit;

use AppDevPanel\Adapter\Yii2\Bootstrap;
use AppDevPanel\Adapter\Yii2\Module;
use PHPUnit\Framework\TestCase;
use yii\web\Application;

final class BootstrapTest extends TestCase
{
    public function testBootstrapRegistersModuleWhenNotPresent(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('hasModule')->with('adp')->willReturn(false);

        $module = $this->createMock(Module::class);
        $module->expects($this->once())->method('bootstrap')->with($app);

        $app->expects($this->once())->method('setModule')->with('adp', $this->isType('array'));
        $app->method('getModule')->with('adp')->willReturn($module);

        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);
    }

    public function testBootstrapSkipsWhenExplicitlyDisabled(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('hasModule')->with('adp')->willReturn(true);
        $app->method('getModules')->willReturn([
            'adp' => ['class' => Module::class, 'enabled' => false],
        ]);

        $app->expects($this->never())->method('setModule');

        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);
    }

    public function testBootstrapBootstrapsExistingModule(): void
    {
        $module = $this->createMock(Module::class);
        $module->expects($this->once())->method('bootstrap');

        $app = $this->createMock(Application::class);
        $app->method('hasModule')->with('adp')->willReturn(true);
        $app->method('getModules')->willReturn([
            'adp' => ['class' => Module::class],
        ]);
        $app->method('getModule')->with('adp')->willReturn($module);

        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);
    }
}
