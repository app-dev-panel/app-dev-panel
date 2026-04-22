<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Test;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;

use function array_values;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function ksort;
use function mkdir;
use function rtrim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @internal used by {@see PHPUnitJSONReporter}
 */
final class PHPUnitReportCollector
{
    /** @var array<string, array{file: string, test: string, status: string, message: string, stacktrace: string}> */
    private array $data = [];

    public function __construct(
        private readonly string $outputPath,
    ) {}

    public function recordPassed(Test $test): void
    {
        $name = $this->name($test);
        // Do not overwrite a recorded failure with a later "passed" retry signal.
        if (isset($this->data[$name])) {
            return;
        }
        $this->data[$name] = [
            'file' => $test->file(),
            'test' => $name,
            'status' => 'ok',
            'message' => '',
            'stacktrace' => '',
        ];
    }

    public function recordFailure(Test $test, string $status, Throwable $throwable): void
    {
        $name = $this->name($test);
        $this->data[$name] = [
            'file' => $test->file(),
            'test' => $name,
            'status' => $status,
            'message' => $throwable->message(),
            'stacktrace' => $throwable->stackTrace(),
        ];
    }

    public function recordSkipped(Test $test, string $message): void
    {
        $name = $this->name($test);
        $this->data[$name] = [
            'file' => $test->file(),
            'test' => $name,
            'status' => 'skipped',
            'message' => $message,
            'stacktrace' => '',
        ];
    }

    public function writeReport(): void
    {
        $dir = rtrim($this->outputPath, DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0o755, true);
        }
        ksort($this->data);
        file_put_contents($dir . DIRECTORY_SEPARATOR . PHPUnitJSONReporter::FILENAME, json_encode(
            array_values($this->data),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function name(Test $test): string
    {
        if ($test instanceof TestMethod) {
            return $test->className() . '::' . $test->methodName();
        }
        return $test->name();
    }
}
