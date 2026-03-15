<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Gitonomy\Git\Commit;
use Gitonomy\Git\Reference\Branch;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final class GitController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private GitRepositoryProvider $repositoryProvider,
    ) {}

    public function summary(): ResponseInterface
    {
        $git = $this->repositoryProvider->get();

        $references = $git->getReferences();
        $name = trim($git->run('branch', ['--show-current']));
        $branch = $references->getBranch($name);
        $branches = $references->getBranches();
        $remoteNames = explode("\n", trim($git->run('remote')));

        $result = [
            'currentBranch' => $branch->getName(),
            'sha' => $branch->getCommitHash(),
            'remotes' => array_map(static fn(string $name) => [
                'name' => $name,
                'url' => trim($git->run('remote', ['get-url', $name])),
            ], $remoteNames),
            'branches' => array_map(static fn(Branch $branch) => $branch->getName(), $branches),
            'lastCommit' => $this->serializeCommit($branch->getCommit()),
            'status' => explode("\n", $git->run('status')),
        ];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function log(): ResponseInterface
    {
        $git = $this->repositoryProvider->get();

        $references = $git->getReferences(false);
        $name = trim($git->run('branch', ['--show-current']));
        $branch = $references->getBranch($name);
        $result = [
            'currentBranch' => $branch->getName(),
            'sha' => $branch->getCommitHash(),
            'commits' => array_map($this->serializeCommit(...), $git->getLog(limit: 20)->getCommits()),
        ];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function checkout(ServerRequestInterface $request): ResponseInterface
    {
        $git = $this->repositoryProvider->get();

        $parsedBody = \json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $branch = $parsedBody['branch'] ?? null;

        if ($branch === null || $branch === '') {
            throw new InvalidArgumentException('Branch should not be empty.');
        }

        if (!preg_match('/^[a-zA-Z0-9\/_.\-]+$/', $branch)) {
            throw new InvalidArgumentException(sprintf('Invalid branch name "%s".', $branch));
        }

        $git->getWorkingCopy()->checkout($branch);
        return $this->responseFactory->createResponse([]);
    }

    public function command(ServerRequestInterface $request): ResponseInterface
    {
        $git = $this->repositoryProvider->get();
        $availableCommands = ['pull', 'fetch'];

        $command = $request->getQueryParams()['command'] ?? null;

        if ($command === null) {
            throw new InvalidArgumentException('Command should not be empty.');
        }
        if (!in_array($command, $availableCommands, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown command "%s". Available commands: "%s".',
                $command,
                implode('", "', $availableCommands),
            ));
        }

        if ($command === 'pull') {
            $git->run('pull', ['--rebase=false']);
        } elseif ($command === 'fetch') {
            $git->run('fetch', ['--tags']);
        }
        return $this->responseFactory->createResponse([]);
    }

    private function serializeCommit(?Commit $commit): array
    {
        return (
            $commit === null
                ? []
                : [
                    'sha' => $commit->getShortHash(),
                    'message' => $commit->getSubjectMessage(),
                    'author' => [
                        'name' => $commit->getAuthorName(),
                        'email' => $commit->getAuthorEmail(),
                    ],
                ]
        );
    }
}
