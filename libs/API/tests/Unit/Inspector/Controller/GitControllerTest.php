<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\GitController;
use InvalidArgumentException;
use Yiisoft\Aliases\Aliases;

final class GitControllerTest extends ControllerTestCase
{
    private function createController(): GitController
    {
        // Point to the actual repo root so git operations work
        $aliases = new Aliases(['@root' => dirname(__DIR__, 6)]);
        return new GitController($this->createResponseFactory(), $aliases);
    }

    public function testSummary(): void
    {
        $controller = $this->createController();
        $response = $controller->summary();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testLog(): void
    {
        $controller = $this->createController();
        $response = $controller->log();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckoutEmptyBranch(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('should not be empty');
        $controller->checkout($this->post(['branch' => '']));
    }

    public function testCheckoutNullBranch(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->checkout($this->post([]));
    }

    public function testCheckoutInvalidBranchName(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch name');
        $controller->checkout($this->post(['branch' => 'foo; rm -rf /']));
    }

    public function testCommandNullCommand(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('should not be empty');
        $controller->command($this->get());
    }

    public function testCommandUnknownCommand(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown command');
        $controller->command($this->get(['command' => 'push']));
    }

    public function testGetGitNotFound(): void
    {
        $aliases = new Aliases(['@root' => '/tmp']);
        $controller = new GitController($this->createResponseFactory(), $aliases);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('repositories');
        $controller->summary();
    }
}
