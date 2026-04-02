<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\TestoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class TestoCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(TestoCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Testo', TestoCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', TestoCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/testo', TestoCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenTestoNotInstalled(): void
    {
        // testo/testo is NOT installed in this project
        $this->assertFalse(TestoCommand::isAvailable());
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(TestoCommand::isAvailable());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinarySuccess(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-testo-ok-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "All tests passed"
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/testo', $script);
        chmod($tmpDir . '/vendor/bin/testo', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new TestoCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertStringContainsString('All tests passed', $response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/testo');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryError(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-testo-err-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "1 test failed"
            exit 1
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/testo', $script);
        chmod($tmpDir . '/vendor/bin/testo', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new TestoCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
            $this->assertStringContainsString('1 test failed', $response->getResult());
        } finally {
            @unlink($tmpDir . '/vendor/bin/testo');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryFail(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-testo-fail-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "Fatal error"
            exit 2
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/testo', $script);
        chmod($tmpDir . '/vendor/bin/testo', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new TestoCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
            $this->assertNull($response->getResult());
            $this->assertNotEmpty($response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/testo');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunCombinesStdoutAndStderr(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-testo-combined-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            echo "stdout line"
            echo "stderr line" >&2
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/testo', $script);
        chmod($tmpDir . '/vendor/bin/testo', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new TestoCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertStringContainsString('stdout line', $response->getResult());
            $this->assertStringContainsString('stderr line', $response->getResult());
        } finally {
            @unlink($tmpDir . '/vendor/bin/testo');
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
