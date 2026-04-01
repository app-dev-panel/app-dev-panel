<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use Gitonomy\Git\Commit;
use Gitonomy\Git\Log;
use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\ReferenceBag;
use Gitonomy\Git\Repository;
use Gitonomy\Git\WorkingCopy;
use InvalidArgumentException;

final class GitControllerTest extends ControllerTestCase
{
    private function createMockRepository(): Repository
    {
        $commit = $this->createMock(Commit::class);
        $commit->method('getShortHash')->willReturn('abc1234');
        $commit->method('getSubjectMessage')->willReturn('Initial commit');
        $commit->method('getAuthorName')->willReturn('Test Author');
        $commit->method('getAuthorEmail')->willReturn('test@example.com');

        $branch = $this->createMock(Branch::class);
        $branch->method('getName')->willReturn('main');
        $branch->method('getCommitHash')->willReturn('abc1234567890');
        $branch->method('getCommit')->willReturn($commit);

        $references = $this->createMock(ReferenceBag::class);
        $references->method('getBranch')->with('main')->willReturn($branch);
        $references->method('getBranches')->willReturn([$branch]);

        $log = $this->createMock(Log::class);
        $log->method('getCommits')->willReturn([$commit]);

        $workingCopy = $this->createMock(WorkingCopy::class);

        $repository = $this->createMock(Repository::class);
        $repository->method('getReferences')->willReturn($references);
        $repository
            ->method('run')
            ->willReturnMap([
                ['branch', ['--show-current'], "main\n"],
                ['remote', [], "origin\n"],
                ['remote', ['get-url', 'origin'], "git@github.com:test/repo.git\n"],
                ['status', [], "On branch main\nnothing to commit"],
                ['pull', ['--rebase=false'], ''],
                ['fetch', ['--tags'], ''],
            ]);
        $repository->method('getLog')->willReturn($log);
        $repository->method('getWorkingCopy')->willReturn($workingCopy);

        return $repository;
    }

    private function createController(?Repository $repository = null): GitController
    {
        $provider = $this->createMock(GitRepositoryProvider::class);
        $provider->method('get')->willReturn($repository ?? $this->createMockRepository());

        return new GitController($this->createResponseFactory(), $provider);
    }

    public function testSummary(): void
    {
        $controller = $this->createController();
        $response = $controller->summary($this->get());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testLog(): void
    {
        $controller = $this->createController();
        $response = $controller->log($this->get());

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
        $provider = $this->createMock(GitRepositoryProvider::class);
        $provider
            ->method('get')
            ->willThrowException(new InvalidArgumentException('Could find any repositories up from "/" directory.'));
        $controller = new GitController($this->createResponseFactory(), $provider);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('repositories');
        $controller->summary($this->get());
    }

    public function testCheckoutValidBranch(): void
    {
        $workingCopy = $this->createMock(WorkingCopy::class);
        $workingCopy
            ->expects($this->once())
            ->method('checkout')
            ->with('feature/my-branch');

        $repository = $this->createMockRepository();
        // Override getWorkingCopy to return our mock
        $repository = $this->createMock(Repository::class);
        $repository->method('getWorkingCopy')->willReturn($workingCopy);

        $controller = $this->createController($repository);
        $response = $controller->checkout($this->post(['branch' => 'feature/my-branch']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckoutValidBranchWithDots(): void
    {
        $workingCopy = $this->createMock(WorkingCopy::class);
        $workingCopy->expects($this->once())->method('checkout')->with('release/v1.2.3');

        $repository = $this->createMock(Repository::class);
        $repository->method('getWorkingCopy')->willReturn($workingCopy);

        $controller = $this->createController($repository);
        $response = $controller->checkout($this->post(['branch' => 'release/v1.2.3']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckoutWithSpecialCharsInBranchNameFails(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid branch name');
        $controller->checkout($this->post(['branch' => 'branch name with spaces']));
    }

    public function testCommandPull(): void
    {
        $repository = $this->createMockRepository();

        $controller = $this->createController($repository);
        $response = $controller->command($this->get(['command' => 'pull']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCommandFetch(): void
    {
        $repository = $this->createMockRepository();

        $controller = $this->createController($repository);
        $response = $controller->command($this->get(['command' => 'fetch']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSummaryContainsExpectedKeys(): void
    {
        $controller = $this->createController();
        $response = $controller->summary($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('currentBranch', $data);
        $this->assertArrayHasKey('sha', $data);
        $this->assertArrayHasKey('remotes', $data);
        $this->assertArrayHasKey('branches', $data);
        $this->assertArrayHasKey('lastCommit', $data);
        $this->assertArrayHasKey('status', $data);
    }

    public function testSummaryBranchData(): void
    {
        $controller = $this->createController();
        $response = $controller->summary($this->get());

        $data = $this->responseData($response);
        $this->assertSame('main', $data['currentBranch']);
        $this->assertSame('abc1234567890', $data['sha']);
    }

    public function testSummaryRemotesData(): void
    {
        $controller = $this->createController();
        $response = $controller->summary($this->get());

        $data = $this->responseData($response);
        $this->assertCount(1, $data['remotes']);
        $this->assertSame('origin', $data['remotes'][0]['name']);
        $this->assertSame('git@github.com:test/repo.git', $data['remotes'][0]['url']);
    }

    public function testSummaryLastCommitData(): void
    {
        $controller = $this->createController();
        $response = $controller->summary($this->get());

        $data = $this->responseData($response);
        $this->assertSame('abc1234', $data['lastCommit']['sha']);
        $this->assertSame('Initial commit', $data['lastCommit']['message']);
        $this->assertSame('Test Author', $data['lastCommit']['author']['name']);
        $this->assertSame('test@example.com', $data['lastCommit']['author']['email']);
    }

    public function testLogContainsExpectedKeys(): void
    {
        $controller = $this->createController();
        $response = $controller->log($this->get());

        $data = $this->responseData($response);
        $this->assertArrayHasKey('currentBranch', $data);
        $this->assertArrayHasKey('sha', $data);
        $this->assertArrayHasKey('commits', $data);
        $this->assertCount(1, $data['commits']);
    }
}
