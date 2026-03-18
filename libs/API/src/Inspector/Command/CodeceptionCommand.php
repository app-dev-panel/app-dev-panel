<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Test\CodeceptionJSONReporter;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class CodeceptionCommand implements CommandInterface
{
    public const COMMAND_NAME = 'test/codeception';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

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
        $debugDirectory = $this->pathResolver->getRuntimePath() . '/debug';

        $extension = CodeceptionJSONReporter::class;
        $params = [
            'vendor/bin/codecept',
            'run',
            '--silent',
            '-e',
            $extension,
            '--override',
            "extensions: config: {$extension}: output-path: {$debugDirectory}",
            '-vvv',
        ];

        $process = new Process($params);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $processOutput = json_decode(
            file_get_contents($debugDirectory . DIRECTORY_SEPARATOR . CodeceptionJSONReporter::FILENAME),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

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
