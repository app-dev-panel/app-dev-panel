<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\TestCase;

final class GitRepositoryProviderTest extends TestCase
{
    public function testGetFindsGitRepository(): void
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn(dirname(__DIR__, 5));

        $provider = new GitRepositoryProvider($pathResolver);
        $repo = $provider->get();

        $this->assertNotNull($repo);
    }

    public function testGetThrowsWhenNoRepository(): void
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn('/tmp');

        $provider = new GitRepositoryProvider($pathResolver);

        $this->expectException(\InvalidArgumentException::class);
        $provider->get();
    }
}
