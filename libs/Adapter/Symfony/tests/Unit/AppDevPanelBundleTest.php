<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit;

use AppDevPanel\Adapter\Symfony\AppDevPanelBundle;
use AppDevPanel\Adapter\Symfony\DependencyInjection\AppDevPanelExtension;
use AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppDevPanelBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new AppDevPanelBundle();

        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(AppDevPanelExtension::class, $extension);
        $this->assertSame('app_dev_panel', $extension->getAlias());
    }

    public function testBuildRegistersCompilerPass(): void
    {
        $bundle = new AppDevPanelBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $hasCompilerPass = false;
        foreach ($passes as $pass) {
            if (!$pass instanceof CollectorProxyCompilerPass) {
                continue;
            }

            $hasCompilerPass = true;
            break;
        }

        $this->assertTrue($hasCompilerPass, 'CollectorProxyCompilerPass should be registered');
    }
}
