<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Test;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * PHPUnit 10+ extension that collects test outcomes into a JSON file.
 *
 * The output path is taken from either the {@see PHPUnitJSONReporter::ENVIRONMENT_VARIABLE_DIRECTORY_NAME}
 * environment variable or the `output-path` extension parameter (phpunit.xml).
 * The resulting file has the fixed name {@see PHPUnitJSONReporter::FILENAME} and contains a JSON array of
 * {file, test, status, message, stacktrace} objects — matching the legacy ADP contract.
 */
final class PHPUnitJSONReporter implements Extension
{
    public const FILENAME = 'phpunit-report.json';
    public const ENVIRONMENT_VARIABLE_DIRECTORY_NAME = 'REPORTER_OUTPUT_PATH';

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $outputPath = $this->resolveOutputPath($parameters);
        $collector = new PHPUnitReportCollector($outputPath);

        $facade->registerSubscribers(
            new class($collector) implements PassedSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(Passed $event): void
                {
                    $this->collector->recordPassed($event->test());
                }
            },
            new class($collector) implements FailedSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(Failed $event): void
                {
                    $this->collector->recordFailure($event->test(), 'fail', $event->throwable());
                }
            },
            new class($collector) implements ErroredSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(Errored $event): void
                {
                    $this->collector->recordFailure($event->test(), 'error', $event->throwable());
                }
            },
            new class($collector) implements SkippedSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(Skipped $event): void
                {
                    $this->collector->recordSkipped($event->test(), $event->message());
                }
            },
            new class($collector) implements MarkedIncompleteSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(MarkedIncomplete $event): void
                {
                    $this->collector->recordFailure($event->test(), 'incomplete', $event->throwable());
                }
            },
            new class($collector) implements ExecutionFinishedSubscriber {
                public function __construct(
                    private readonly PHPUnitReportCollector $collector,
                ) {}

                public function notify(ExecutionFinished $event): void
                {
                    $this->collector->writeReport();
                }
            },
        );
    }

    private function resolveOutputPath(ParameterCollection $parameters): string
    {
        if ($parameters->has('output-path')) {
            return $parameters->get('output-path');
        }
        $fromEnv = getenv(self::ENVIRONMENT_VARIABLE_DIRECTORY_NAME);
        return $fromEnv !== false && $fromEnv !== '' ? $fromEnv : (getcwd() ?: '.');
    }
}
