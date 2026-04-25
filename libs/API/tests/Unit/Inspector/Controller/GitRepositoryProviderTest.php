<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\PathResolverInterface;
use Gitonomy\Git\Repository;
use PHPUnit\Framework\TestCase;

final class GitRepositoryProviderTest extends TestCase
{
    private function pathResolver(string $rootPath): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($rootPath);
        return $pathResolver;
    }

    public function testGetFindsGitRepository(): void
    {
        $provider = new GitRepositoryProvider($this->pathResolver(dirname(__DIR__, 5)));
        $repo = $provider->get();

        $this->assertNotNull($repo);
    }

    public function testGetReturnsRepositoryInstance(): void
    {
        $provider = new GitRepositoryProvider($this->pathResolver(dirname(__DIR__, 5)));
        $repo = $provider->get();

        $this->assertInstanceOf(Repository::class, $repo);
    }

    public function testGetFindsRepositoryFromSubdirectory(): void
    {
        // Use a subdirectory — should walk up and find the .git directory
        $subDir = dirname(__DIR__, 5) . '/libs/API';
        $provider = new GitRepositoryProvider($this->pathResolver($subDir));
        $repo = $provider->get();

        $this->assertInstanceOf(Repository::class, $repo);
    }

    public function testGetThrowsWhenNoRepository(): void
    {
        $tempDir = sys_get_temp_dir() . '/adp_git_no_repo_' . uniqid();
        mkdir($tempDir, 0o755, true);

        try {
            $provider = new GitRepositoryProvider($this->pathResolver($tempDir));

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Could find any repositories');
            $provider->get();
        } finally {
            rmdir($tempDir);
        }
    }

    public function testGetThrowsWithNonExistentPath(): void
    {
        $provider = new GitRepositoryProvider($this->pathResolver('/nonexistent/path/does/not/exist'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could find any repositories');
        $provider->get();
    }
}
