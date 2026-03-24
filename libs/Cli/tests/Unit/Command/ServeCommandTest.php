<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\ServeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\PhpExecutableFinder;

final class ServeCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $command = new ServeCommand();

        $this->assertSame('serve', $command->getName());
    }

    public function testCommandDescription(): void
    {
        $command = new ServeCommand();

        $this->assertSame('Start standalone ADP API server', $command->getDescription());
    }

    public function testDefaultOptions(): void
    {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('storage-path'));
        $this->assertTrue($definition->hasOption('root-path'));
        $this->assertTrue($definition->hasOption('runtime-path'));
        $this->assertTrue($definition->hasOption('frontend-path'));

        $this->assertSame('127.0.0.1', $definition->getOption('host')->getDefault());
        $this->assertSame('8888', $definition->getOption('port')->getDefault());
        $this->assertSame('p', $definition->getOption('port')->getShortcut());
    }

    public function testHostOptionIsOptional(): void
    {
        $command = new ServeCommand();
        $option = $command->getDefinition()->getOption('host');

        $this->assertFalse($option->isValueRequired());
    }

    public function testStoragePathOptionDefault(): void
    {
        $command = new ServeCommand();
        $option = $command->getDefinition()->getOption('storage-path');

        $this->assertNull($option->getDefault());
    }

    public function testRootPathOptionDefault(): void
    {
        $command = new ServeCommand();
        $option = $command->getDefinition()->getOption('root-path');

        $this->assertSame(getcwd(), $option->getDefault());
    }

    public function testFrontendPathOptionDefault(): void
    {
        $command = new ServeCommand();
        $option = $command->getDefinition()->getOption('frontend-path');

        $this->assertNull($option->getDefault());
    }

    public function testRuntimePathOptionDefault(): void
    {
        $command = new ServeCommand();
        $option = $command->getDefinition()->getOption('runtime-path');

        $this->assertNull($option->getDefault());
    }

    public function testExecuteWithInvalidHostExitsWithFailure(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp-test-' . uniqid();

        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
            '--storage-path' => $storagePath,
        ]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('ADP Standalone Server', $display);
        $this->assertStringContainsString('Starting server', $display);
        $this->assertStringContainsString($storagePath, $display);

        // Process exits with non-zero because the invalid host fails
        $this->assertNotSame(0, $tester->getStatusCode());

        // Clean up
        if (is_dir($storagePath)) {
            rmdir($storagePath);
        }
    }

    public function testExecuteCreatesStorageDirectory(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp-test-serve-' . uniqid();
        $this->assertDirectoryDoesNotExist($storagePath);

        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
            '--storage-path' => $storagePath,
        ]);

        $this->assertDirectoryExists($storagePath);

        // Clean up
        rmdir($storagePath);
    }

    public function testExecuteWithFrontendPath(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp-test-fp-' . uniqid();
        $frontendPath = sys_get_temp_dir() . '/adp-test-frontend-' . uniqid();
        mkdir($frontendPath, 0o777, true);

        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
            '--storage-path' => $storagePath,
            '--frontend-path' => $frontendPath,
        ]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString($frontendPath, $display);

        // Clean up
        if (is_dir($storagePath)) {
            rmdir($storagePath);
        }
        rmdir($frontendPath);
    }

    public function testExecuteWithoutFrontendPathShowsNotConfigured(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp-test-nofp-' . uniqid();

        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
            '--storage-path' => $storagePath,
        ]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('(not configured)', $display);

        // Clean up
        if (is_dir($storagePath)) {
            rmdir($storagePath);
        }
    }

    public function testExecuteWithRuntimePath(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp-test-rp-' . uniqid();
        $runtimePath = sys_get_temp_dir() . '/adp-test-runtime';

        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
            '--storage-path' => $storagePath,
            '--runtime-path' => $runtimePath,
        ]);

        $this->assertStringContainsString('ADP Standalone Server', $tester->getDisplay());

        // Clean up
        if (is_dir($storagePath)) {
            rmdir($storagePath);
        }
    }

    public function testExecuteUsesDefaultStoragePath(): void
    {
        $command = new ServeCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--host' => '999.999.999.999',
            '--port' => '1',
        ]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString(sys_get_temp_dir() . '/adp', $display);
    }

    public function testRouterScriptNotFound(): void
    {
        $command = new ServeCommand('/nonexistent/router.php');
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Server router script not found', $tester->getDisplay());
    }

    public function testPhpBinaryNotFound(): void
    {
        $finder = $this->createMock(PhpExecutableFinder::class);
        $finder->method('find')->willReturn(false);

        $command = new ServeCommand(null, $finder);
        $tester = new CommandTester($command);

        $storagePath = sys_get_temp_dir() . '/adp-test-nophp-' . uniqid();

        $tester->execute([
            '--storage-path' => $storagePath,
        ]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('PHP binary not found', $tester->getDisplay());

        // Clean up
        if (is_dir($storagePath)) {
            rmdir($storagePath);
        }
    }
}
