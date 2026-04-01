<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PestCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;

final class PestCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PestCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Pest', PestCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PestCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/pest', PestCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenPestNotInstalled(): void
    {
        // pestphp/pest is not installed in this project
        $this->assertFalse(PestCommand::isAvailable());
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(PestCommand::isAvailable());
    }

    public function testRunWithMockBinarySuccess(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-pest-ok-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "Tests: 5 passed"
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/pest', $script);
        chmod($tmpDir . '/vendor/bin/pest', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PestCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertStringContainsString('Tests: 5 passed', $response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/pest');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    public function testRunWithMockBinaryError(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-pest-err-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "Tests: 1 failed"
            exit 1
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/pest', $script);
        chmod($tmpDir . '/vendor/bin/pest', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PestCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
            $this->assertStringContainsString('Tests: 1 failed', $response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/pest');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    public function testRunWithMockBinaryFail(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-pest-fail-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "Fatal error occurred"
            exit 2
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/pest', $script);
        chmod($tmpDir . '/vendor/bin/pest', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PestCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
            $this->assertNull($response->getResult());
            $this->assertNotEmpty($response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/pest');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    public function testRunCombinesStdoutAndStderr(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-pest-combined-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "stdout output"
            echo "stderr output" >&2
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/pest', $script);
        chmod($tmpDir . '/vendor/bin/pest', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PestCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertStringContainsString('stdout output', $response->getResult());
            $this->assertStringContainsString('stderr output', $response->getResult());
        } finally {
            @unlink($tmpDir . '/vendor/bin/pest');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    private function createPathResolver(string $rootPath): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($rootPath);
        $pathResolver->method('getRuntimePath')->willReturn($rootPath . '/runtime');
        return $pathResolver;
    }
}
