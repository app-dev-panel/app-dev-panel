<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Gitonomy\Git\Commit;
use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Repository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final class GitController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function summary(): ResponseInterface
    {
        $git = $this->getGit();

        $references = $git->getReferences();
        $name = trim($git->run('branch', ['--show-current']));
        $branch = $references->getBranch($name);
        $branches = $references->getBranches();
        $remoteNames = explode("\n", trim($git->run('remote')));

        $result = [
            'currentBranch' => $branch->getName(),
            'sha' => $branch->getCommitHash(),
            'remotes' => array_map(fn (string $name) => [
                'name' => $name,
                'url' => trim($git->run('remote', ['get-url', $name])),
            ], $remoteNames),
            'branches' => array_map(fn (Branch $branch) => $branch->getName(), $branches),
            'lastCommit' => $this->serializeCommit($branch->getCommit()),
            'status' => explode("\n", $git->run('status')),
        ];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function log(): ResponseInterface
    {
        $git = $this->getGit();

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
        $git = $this->getGit();

        $parsedBody = \json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $branch = $parsedBody['branch'] ?? null;

        if ($branch === null) {
            throw new InvalidArgumentException('Branch should not be empty.');
        }

        $git->getWorkingCopy()->checkout($branch);
        return $this->responseFactory->createResponse([]);
    }

    public function command(ServerRequestInterface $request): ResponseInterface
    {
        $git = $this->getGit();
        $availableCommands = ['pull', 'fetch'];

        $command = $request->getQueryParams()['command'] ?? null;

        if ($command === null) {
            throw new InvalidArgumentException('Command should not be empty.');
        }
        if (!in_array($command, $availableCommands, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown command "%s". Available commands: "%s".',
                    $command,
                    implode('", "', $availableCommands),
                )
            );
        }

        if ($command === 'pull') {
            $git->run('pull', ['--rebase=false']);
        } elseif ($command === 'fetch') {
            $git->run('fetch', ['--tags']);
        }
        return $this->responseFactory->createResponse([]);
    }

    private function getGit(): Repository
    {
        $projectPath = $this->aliases->get('@root');

        while ($projectPath !== '/') {
            try {
                $git = new Repository($projectPath);
                $git->getWorkingCopy();
                return $git;
            } catch (Throwable) {
                $projectPath = dirname($projectPath);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Could find any repositories up from "%s" directory.',
                $projectPath,
            )
        );
    }

    private function serializeCommit(?Commit $commit): array
    {
        return $commit === null ? [] : [
            'sha' => $commit->getShortHash(),
            'message' => $commit->getSubjectMessage(),
            'author' => [
                'name' => $commit->getAuthorName(),
                'email' => $commit->getAuthorEmail(),
            ],
        ];
    }
}
