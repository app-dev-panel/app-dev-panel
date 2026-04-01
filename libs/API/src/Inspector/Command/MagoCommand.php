<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class MagoCommand implements CommandInterface
{
    public const COMMAND_NAME = 'analyse/mago';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        if (\Composer\InstalledVersions::isInstalled('carthage-software/mago')) {
            return true;
        }

        $check = DIRECTORY_SEPARATOR === '\\' ? 'where mago 2>NUL' : 'command -v mago 2>/dev/null';

        return !empty(trim((string) @shell_exec($check)));
    }

    public static function getTitle(): string
    {
        return 'Mago';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): CommandResponse
    {
        $projectDirectory = $this->pathResolver->getRootPath();

        $binary = \Composer\InstalledVersions::isInstalled('carthage-software/mago')
            ? $projectDirectory . '/vendor/bin/mago'
            : 'mago';

        $params = [
            $binary,
            'lint',
        ];

        $process = new Process($params);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = rtrim($process->getOutput() . $process->getErrorOutput());

        if ($process->getExitCode() > 1) {
            return new CommandResponse(status: CommandResponse::STATUS_FAIL, result: null, errors: array_filter([trim(
                $processOutput,
            )]));
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $processOutput,
        );
    }
}
