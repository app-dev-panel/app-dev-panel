<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\Inspector\Test\CodeceptionJSONReporter;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

use function array_filter;
use function array_values;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function json_decode;
use function mkdir;
use function rtrim;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

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
        $debugDirectory = $this->pathResolver->getRuntimePath() . '/debug';

        if (!is_dir($debugDirectory)) {
            @mkdir($debugDirectory, 0o755, true);
        }

        $extension = CodeceptionJSONReporter::class;

        $process = new Process([
            'vendor/bin/codecept',
            'run',
            '--no-colors',
            '--silent',
            '-e',
            $extension,
            '--override',
            "extensions: config: {$extension}: output-path: {$debugDirectory}",
        ]);

        $process->setWorkingDirectory($projectDirectory)->setTimeout(null)->run();

        $reportPath =
            rtrim($debugDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . CodeceptionJSONReporter::FILENAME;
        $processOutput = rtrim($process->getOutput());
        $processErrors = rtrim($process->getErrorOutput());

        $report = $this->readReport($reportPath);

        if ($report === null) {
            $combined = trim($processOutput . "\n" . $processErrors);
            return new CommandResponse(
                status: $process->getExitCode() > 1 ? CommandResponse::STATUS_FAIL : CommandResponse::STATUS_ERROR,
                result: $combined === '' ? null : $combined,
                errors: array_values(array_filter([
                    $combined === '' ? 'Codeception produced no output and no JSON report.' : null,
                ])),
            );
        }

        if ($process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: $report,
                errors: array_values(array_filter([$processErrors])),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful() ? CommandResponse::STATUS_OK : CommandResponse::STATUS_ERROR,
            result: $report,
        );
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function readReport(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }
        try {
            /** @var array<int, array<string, mixed>> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (\JsonException) {
            return null;
        }
    }
}
