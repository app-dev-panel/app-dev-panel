<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use Gitonomy\Git\Repository;
use InvalidArgumentException;
use Throwable;
use Yiisoft\Aliases\Aliases;

class GitRepositoryProvider
{
    public function __construct(
        private Aliases $aliases,
    ) {}

    public function get(): Repository
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

        throw new InvalidArgumentException(sprintf(
            'Could find any repositories up from "%s" directory.',
            $projectPath,
        ));
    }
}
