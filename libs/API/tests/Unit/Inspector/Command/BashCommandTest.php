<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;

final class BashCommandTest extends TestCase
{
    public function testSuccess(): void
    {
        $pathResolver = $this->createPathResolver(__DIR__);
        $command = new BashCommand($pathResolver, ['echo', 'test']);

        $response = $command->run();

        $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
        $this->assertSame('test', $response->getResult());
        $this->assertSame([], $response->getErrors());
    }

    public function testError(): void
    {
        $pathResolver = $this->createPathResolver(dirname(__DIR__, 3) . '/Support/Application');
        $command = new BashCommand($pathResolver, ['bash', 'fail.sh', '1']);

        $response = $command->run();

        $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
        $this->assertSame('failed', $response->getResult());
        $this->assertSame([], $response->getErrors());
    }

    public function testFail(): void
    {
        $pathResolver = $this->createPathResolver(dirname(__DIR__, 3) . '/Support/Application');
        $command = new BashCommand($pathResolver, ['bash', 'fail.sh', '2']);

        $response = $command->run();

        $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
        $this->assertNull($response->getResult());
        $this->assertNotEmpty($response->getErrors());
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Bash', BashCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('Runs any commands from the project root.', BashCommand::getDescription());
    }

    private function createPathResolver(string $rootPath): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($rootPath);
        $pathResolver->method('getRuntimePath')->willReturn($rootPath . '/runtime');
        return $pathResolver;
    }
}
