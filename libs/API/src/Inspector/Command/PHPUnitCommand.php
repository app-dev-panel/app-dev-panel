<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Inspector\CommandInterface;
use Yiisoft\Yii\Debug\Api\Inspector\CommandResponse;
use Yiisoft\Yii\Debug\Api\Inspector\Test\PHPUnitJSONReporter;

class PHPUnitCommand implements CommandInterface
{
    public const COMMAND_NAME = 'test/phpunit';

    public function __construct(private Aliases $aliases)
    {
    }

    public static function getTitle(): string
    {
        return 'PHPUnit';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): CommandResponse
    {
        $projectDirectory = $this->aliases->get('@root');
        $debugDirectory = $this->aliases->get('@runtime/debug');

        $extension = PHPUnitJSONReporter::class;
        $params = [
            'vendor/bin/phpunit',
            '--printer',
            $extension,
            '-vvv',
        ];

        $process = new Process($params);

        $process
            ->setEnv([PHPUnitJSONReporter::ENVIRONMENT_VARIABLE_DIRECTORY_NAME => $debugDirectory])
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        $processOutput = json_decode(
            file_get_contents($debugDirectory . DIRECTORY_SEPARATOR . PHPUnitJSONReporter::FILENAME),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!$process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_filter([$processOutput, $process->getErrorOutput()]),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $processOutput
        );
    }
}
