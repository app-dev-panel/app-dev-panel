<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\PathResolverInterface;
use Gitonomy\Git\Repository;
use InvalidArgumentException;
use Throwable;

class GitRepositoryProvider
{
    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public function get(): Repository
    {
        $projectPath = $this->pathResolver->getRootPath();

        while ($projectPath !== '/') {
            try {
                $git = new Repository($projectPath);
                $git->getWorkingCopy();
                return $git;
            } catch (Throwable) {
                $projectPath = dirname($projectPath);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Could find any repositories up from "%s" directory.',
            $projectPath,
        ));
    }
}
