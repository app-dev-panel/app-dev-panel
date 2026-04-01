<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Collector;

use AppDevPanel\Adapter\Laravel\Collector\TemplateCollectorCompilerEngine;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\CompilerInterface;
use PHPUnit\Framework\TestCase;

final class TemplateCollectorCompilerEngineTest extends TestCase
{
    public function testGetWithCollectorCapturesRenderData(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $templateCollector = new TemplateCollector($timeline);
        $templateCollector->startup();

        $tmpFile = tempnam(sys_get_temp_dir(), 'blade_');
        file_put_contents($tmpFile, '<?php echo "Hello World"; ?>');

        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('isExpired')->willReturn(false);
        $compiler->method('getCompiledPath')->willReturn($tmpFile);

        $engine = new TemplateCollectorCompilerEngine($compiler, new Filesystem());
        $engine->setCollector($templateCollector);

        $result = $engine->get($tmpFile);

        $this->assertSame('Hello World', $result);

        $collected = $templateCollector->getCollected();
        $this->assertCount(1, $collected['renders']);
        $this->assertSame($tmpFile, $collected['renders'][0]['template']);
        $this->assertGreaterThanOrEqual(0, $collected['renders'][0]['renderTime']);

        @unlink($tmpFile);
    }

    public function testGetWithoutCollectorWorksNormally(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'blade_');
        file_put_contents($tmpFile, '<?php echo "No Collector"; ?>');

        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('isExpired')->willReturn(false);
        $compiler->method('getCompiledPath')->willReturn($tmpFile);

        $engine = new TemplateCollectorCompilerEngine($compiler, new Filesystem());

        $result = $engine->get($tmpFile);

        $this->assertSame('No Collector', $result);

        @unlink($tmpFile);
    }

    public function testMultipleSequentialRenders(): void
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $templateCollector = new TemplateCollector($timeline);
        $templateCollector->startup();

        $file1 = tempnam(sys_get_temp_dir(), 'blade_');
        $file2 = tempnam(sys_get_temp_dir(), 'blade_');
        file_put_contents($file1, '<?php echo "first"; ?>');
        file_put_contents($file2, '<?php echo "second"; ?>');

        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('isExpired')->willReturn(false);
        $compiler->method('getCompiledPath')->willReturnCallback(static fn(string $path) => $path);

        $engine = new TemplateCollectorCompilerEngine($compiler, new Filesystem());
        $engine->setCollector($templateCollector);

        $engine->get($file1);
        $engine->get($file2);

        $collected = $templateCollector->getCollected();
        $this->assertCount(2, $collected['renders']);
        $this->assertSame($file1, $collected['renders'][0]['template']);
        $this->assertSame($file2, $collected['renders'][1]['template']);

        @unlink($file1);
        @unlink($file2);
    }
}
