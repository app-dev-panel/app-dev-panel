<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class MagoCommand implements CommandInterface
{
    use ProcessCommandTrait;

    public const COMMAND_NAME = 'analyse/mago';

    private const float BINARY_LOOKUP_TIMEOUT = 5.0;

    /** Memoised `command -v mago` result — the lookup spawns a process, so do it once per PHP process. */
    private static ?bool $binaryOnPath = null;

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        if (\Composer\InstalledVersions::isInstalled('carthage-software/mago')) {
            return true;
        }

        return self::isBinaryOnPath();
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

        if (!$this->runProcess($process, $projectDirectory)) {
            return $this->timedOutResponse();
        }

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

    private static function isBinaryOnPath(): bool
    {
        if (self::$binaryOnPath !== null) {
            return self::$binaryOnPath;
        }

        $process = DIRECTORY_SEPARATOR === '\\'
            ? new Process(['where', 'mago'])
            : Process::fromShellCommandline('command -v mago');
        $process->setTimeout(self::BINARY_LOOKUP_TIMEOUT);

        try {
            $process->run();
            self::$binaryOnPath = $process->isSuccessful() && trim($process->getOutput()) !== '';
        } catch (\Throwable) {
            self::$binaryOnPath = false;
        }

        return self::$binaryOnPath;
    }
}
