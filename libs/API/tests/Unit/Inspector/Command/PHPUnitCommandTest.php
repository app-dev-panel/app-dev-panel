<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Test\PHPUnitJSONReporter;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;

final class PHPUnitCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PHPUnitCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('PHPUnit', PHPUnitCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PHPUnitCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/phpunit', PHPUnitCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsTrueWhenPHPUnitInstalled(): void
    {
        // phpunit/phpunit IS installed in this project
        $this->assertTrue(PHPUnitCommand::isAvailable());
    }

    public function testRunWithMockBinarySuccess(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpunit-ok-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        $filename = PHPUnitJSONReporter::FILENAME;
        $script = <<<BASH
            #!/bin/bash
            OUTPUT_DIR="\$REPORTER_OUTPUT_PATH"
            echo '{"tests":[],"assertions":0,"errors":0,"failures":0}' > "\$OUTPUT_DIR/$filename"
            exit 0
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpunit', $script);
        chmod($tmpDir . '/vendor/bin/phpunit', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPUnitCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertIsArray($response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($debugDir . '/' . $filename);
            @unlink($tmpDir . '/vendor/bin/phpunit');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    public function testRunWithMockBinaryError(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpunit-err-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        $filename = PHPUnitJSONReporter::FILENAME;
        $script = <<<BASH
            #!/bin/bash
            OUTPUT_DIR="\$REPORTER_OUTPUT_PATH"
            echo '{"tests":[{"name":"testFoo","status":"fail"}],"assertions":1,"errors":0,"failures":1}' > "\$OUTPUT_DIR/$filename"
            exit 1
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpunit', $script);
        chmod($tmpDir . '/vendor/bin/phpunit', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPUnitCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
            $this->assertIsArray($response->getResult());
        } finally {
            @unlink($debugDir . '/' . $filename);
            @unlink($tmpDir . '/vendor/bin/phpunit');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    public function testRunWithMockBinaryFail(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-phpunit-fail-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        $filename = PHPUnitJSONReporter::FILENAME;
        $script = <<<BASH
            #!/bin/bash
            OUTPUT_DIR="\$REPORTER_OUTPUT_PATH"
            echo '{"error":"Fatal error occurred"}' > "\$OUTPUT_DIR/$filename"
            echo "PHPUnit fatal error" >&2
            exit 2
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/phpunit', $script);
        chmod($tmpDir . '/vendor/bin/phpunit', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PHPUnitCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
            $this->assertNull($response->getResult());
            $this->assertNotEmpty($response->getErrors());
        } finally {
            @unlink($debugDir . '/' . $filename);
            @unlink($tmpDir . '/vendor/bin/phpunit');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
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
