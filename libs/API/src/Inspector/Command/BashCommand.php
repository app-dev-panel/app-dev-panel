<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

final class BashCommand implements CommandInterface
{
    public function __construct(
        private readonly PathResolverInterface $pathResolver,
        private readonly array $command,
    ) {}

    public static function getTitle(): string
    {
        return 'Bash';
    }

    public static function getDescription(): string
    {
        return 'Runs any commands from the project root.';
    }

    public function run(): CommandResponse
    {
        $projectDirectory = $this->pathResolver->getRootPath();

        $process = new Process($this->command);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = rtrim($process->getOutput());

        if ($process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_filter([$processOutput, $process->getErrorOutput()]),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $processOutput . $process->getErrorOutput(),
        );
    }
}
