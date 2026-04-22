<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

use function array_filter;
use function array_values;
use function rtrim;
use function trim;

/**
 * Runs Codeception without a custom reporter and returns raw stdout+stderr.
 * Companion to {@see CodeceptionCommand}, which produces a structured JSON report.
 */
class CodeceptionRawCommand implements CommandInterface
{
    public const COMMAND_NAME = 'test/codeception-raw';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        return \Composer\InstalledVersions::isInstalled('codeception/codeception');
    }

    public static function getTitle(): string
    {
        return 'Codeception (raw)';
    }

    public static function getDescription(): string
    {
        return 'Runs Codeception and returns the raw textual output.';
    }

    public function run(): CommandResponse
    {
        $process = new Process(['vendor/bin/codecept', 'run', '--no-colors']);

        $process->setWorkingDirectory($this->pathResolver->getRootPath())->setTimeout(null)->run();

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
