<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Inspector\CommandInterface;
use Yiisoft\Yii\Debug\Api\Inspector\CommandResponse;

final class BashCommand implements CommandInterface
{
    public function __construct(
        private Aliases $aliases,
        private array $command,
    ) {
    }

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
        $projectDirectory = $this->aliases->get('@root');

        $process = new Process($this->command);

        $process
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        $processOutput = rtrim($process->getOutput());

        if (!$process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_filter([$processOutput, $process->getErrorOutput()]),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $processOutput . $process->getErrorOutput()
        );
    }
}
