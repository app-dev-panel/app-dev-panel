<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PHPStanCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class PHPStanCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PHPStanCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('PHPStan', PHPStanCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PHPStanCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/phpstan', PHPStanCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenPHPStanNotInstalled(): void
    {
        // phpstan/phpstan is NOT installed in this project
        $this->assertFalse(PHPStanCommand::isAvailable());
    }

    public function testIsAvailableReturnsBool(): void
    {
        $this->assertIsBool(PHPStanCommand::isAvailable());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunFailsWhenBinaryNotFound(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpstan-test-' . uniqid();
        mkdir($tmpDir, 0755, true);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPStanCommand($pathResolver);

            // vendor/bin/phpstan doesn't exist, process output won't be valid JSON
            // json_decode will throw JsonException
            $this->expectException(\JsonException::class);
            $command->run();
        } finally {
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryThatOutputsJson(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpstan-run-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        // Create a fake phpstan script that outputs valid JSON
        $script = <<<'BASH'
            #!/bin/bash
            echo '{"totals":{"errors":0,"file_errors":0},"files":[],"errors":[]}'
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpstan', $script);
        chmod($tmpDir . '/vendor/bin/phpstan', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPStanCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertIsArray($response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/phpstan');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryThatReportsErrors(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpstan-err-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        // Create a fake phpstan that outputs JSON with errors (exit code 1 = has errors)
        $script = <<<'BASH'
            #!/bin/bash
            echo '{"totals":{"errors":1,"file_errors":1},"files":{"src/Foo.php":{"errors":1,"messages":[{"message":"Error","line":10,"ignorable":true}]}},"errors":[]}'
            exit 1
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpstan', $script);
        chmod($tmpDir . '/vendor/bin/phpstan', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPStanCommand($pathResolver);
            $response = $command->run();

            // Exit code 1 = not successful, but not > 1, so STATUS_ERROR
            $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
            $this->assertIsArray($response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/phpstan');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryThatFails(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpstan-fail-' . uniqid();
        mkdir($tmpDir . '/vendor/bin', 0755, true);

        // Create a fake phpstan that outputs JSON but with exit code 2 (fatal error)
        $script = <<<'BASH'
            #!/bin/bash
            echo '{"totals":{"errors":0,"file_errors":0},"files":[],"errors":["Fatal error"]}'
            exit 2
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpstan', $script);
        chmod($tmpDir . '/vendor/bin/phpstan', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPStanCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
            $this->assertNull($response->getResult());
            $this->assertNotEmpty($response->getErrors());
        } finally {
            @unlink($tmpDir . '/vendor/bin/phpstan');
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
