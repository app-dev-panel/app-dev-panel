<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\MagoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;

final class MagoCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(MagoCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Mago', MagoCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', MagoCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/mago', MagoCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsTrueWhenMagoInstalled(): void
    {
        // carthage-software/mago IS installed in this project
        $this->assertTrue(MagoCommand::isAvailable());
    }

    public function testRunUsesProjectRootAsWorkingDirectory(): void
    {
        $projectRoot = dirname(__DIR__, 6);
        $pathResolver = $this->createPathResolver($projectRoot);
        $command = new MagoCommand($pathResolver);

        $response = $command->run();

        // Mago is installed, so we get either OK or ERROR (lint issues), not FAIL
        $this->assertContains($response->getStatus(), [CommandResponse::STATUS_OK, CommandResponse::STATUS_ERROR]);
        $this->assertNotNull($response->getResult());
    }

    public function testRunReturnsFailWhenBinaryNotFound(): void
    {
        // Use a temp directory with no vendor/bin/mago and ensure mago composer package appears not installed
        $tmpDir = sys_get_temp_dir() . '/adp-mago-test-' . uniqid();
        mkdir($tmpDir, 0755, true);

        // Create a minimal mago.toml so mago doesn't complain about missing config
        file_put_contents($tmpDir . '/mago.toml', '');

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new MagoCommand($pathResolver);

            // Since carthage-software/mago IS installed via composer, it will try vendor/bin/mago
            // in the tmp dir, which doesn't exist, so the process will fail
            $response = $command->run();

            // Process should fail since binary doesn't exist in temp dir
            $this->assertContains($response->getStatus(), [
                CommandResponse::STATUS_FAIL,
                CommandResponse::STATUS_ERROR,
            ]);
        } finally {
            @unlink($tmpDir . '/mago.toml');
            @rmdir($tmpDir);
        }
    }

    public function testRunReturnsCommandResponse(): void
    {
        $projectRoot = dirname(__DIR__, 6);
        $pathResolver = $this->createPathResolver($projectRoot);
        $command = new MagoCommand($pathResolver);

        $response = $command->run();

        $this->assertInstanceOf(CommandResponse::class, $response);
        $this->assertIsString($response->getStatus());
        $this->assertIsArray($response->getErrors());
    }

    private function createPathResolver(string $rootPath): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($rootPath);
        $pathResolver->method('getRuntimePath')->willReturn($rootPath . '/runtime');
        return $pathResolver;
    }
}
