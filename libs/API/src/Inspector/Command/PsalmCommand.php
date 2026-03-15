<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;

class PsalmCommand implements CommandInterface
{
    public const COMMAND_NAME = 'analyse/psalm';

    public function __construct(
        private Aliases $aliases,
    ) {}

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
        $projectDirectory = $this->aliases->get('@root');
        $debugDirectory = $this->aliases->get('@runtime/debug');

        $outputFilePath = $debugDirectory . DIRECTORY_SEPARATOR . 'psalm-report.json';

        $params = [
            'vendor/bin/psalm',
            '--report=' . $outputFilePath,
        ];

        $process = new Process($params);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = json_decode(file_get_contents($outputFilePath), true, 512, JSON_THROW_ON_ERROR);

        if (!$process->getExitCode() > 1) {
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
