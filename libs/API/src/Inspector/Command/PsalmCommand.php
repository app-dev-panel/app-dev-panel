<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class PsalmCommand implements CommandInterface
{
    public const COMMAND_NAME = 'analyse/psalm';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        return \Composer\InstalledVersions::isInstalled('vimeo/psalm');
    }

    public static function getTitle(): string
    {
        return 'Psalm';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): CommandResponse
    {
        $projectDirectory = $this->pathResolver->getRootPath();
        $debugDirectory = $this->pathResolver->getRuntimePath() . '/debug';

        $outputFilePath = $debugDirectory . DIRECTORY_SEPARATOR . 'psalm-report.json';

        $params = [
            'vendor/bin/psalm',
            '--report=' . $outputFilePath,
        ];

        $process = new Process($params);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = json_decode(file_get_contents($outputFilePath), true, 512, JSON_THROW_ON_ERROR);

        if ($process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_filter([$processOutput, $process->getErrorOutput()]),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $processOutput,
        );
    }
}
