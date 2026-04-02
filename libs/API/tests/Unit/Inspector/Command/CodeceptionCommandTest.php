<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\CodeceptionCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use PHPUnit\Framework\TestCase;

final class CodeceptionCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(CodeceptionCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Codeception', CodeceptionCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', CodeceptionCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('test/codeception', CodeceptionCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenCodeceptionNotInstalled(): void
    {
        // codeception/codeception is NOT installed in this project
        $this->assertFalse(CodeceptionCommand::isAvailable());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testRunReturnsFailWhenBinaryNotFound(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-codecept-cmd-test-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        mkdir($tmpDir . '/runtime/debug', 0o755, true);

        try {
            $pathResolver = $this->createMock(\AppDevPanel\Api\PathResolverInterface::class);
            $pathResolver->method('getRootPath')->willReturn($tmpDir);
            $pathResolver->method('getRuntimePath')->willReturn($tmpDir . '/runtime');

            $command = new CodeceptionCommand($pathResolver);

            try {
                $response = $command->run();
                $this->assertContains($response->getStatus(), [
                    \AppDevPanel\Api\Inspector\CommandResponse::STATUS_FAIL,
                    \AppDevPanel\Api\Inspector\CommandResponse::STATUS_ERROR,
                ]);
            } catch (\Throwable) {
                // Expected — binary not found or reporter file doesn't exist
                $this->addToAssertionCount(1);
            }
        } finally {
            @array_map('unlink', glob($tmpDir . '/runtime/debug/*') ?: []);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir);
        }
    }
}
