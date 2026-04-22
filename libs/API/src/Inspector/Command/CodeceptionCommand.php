<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class CodeceptionCommand implements CommandInterface
{
    public const COMMAND_NAME = 'test/codeception';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        return \Composer\InstalledVersions::isInstalled('codeception/codeception');
    }

    public static function getTitle(): string
    {
        return 'Codeception';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): CommandResponse
    {
        $projectDirectory = $this->pathResolver->getRootPath();

        $process = new Process(['vendor/bin/codecept', 'run', '--no-colors']);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = rtrim($process->getOutput());
        $processErrors = rtrim($process->getErrorOutput());

        if ($process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_values(array_filter([$processOutput, $processErrors])),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: trim($processOutput . "\n" . $processErrors),
        );
    }
}
