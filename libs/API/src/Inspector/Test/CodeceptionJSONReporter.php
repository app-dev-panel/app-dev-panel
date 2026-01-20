<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Test;

use Codeception\Event\FailEvent;
use Codeception\Event\PrintResultEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Test\Descriptor;
use ReflectionClass;
use ReflectionObject;

final class CodeceptionJSONReporter extends Extension
{
    protected array $config = [
        'output-path' => __DIR__,
    ];
    private array $data = [];
    public const FILENAME = 'codeception-report.json';

    public function _initialize(): void
    {
        $this->_reconfigure(['settings' => ['silent' => true]]);
    }

    public static array $events = [
        Events::TEST_SUCCESS => 'success',
        Events::TEST_FAIL => 'fail',
        Events::TEST_ERROR => 'error',
        Events::RESULT_PRINT_AFTER => 'all',
    ];

    public function success(TestEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'time' => $event->getTime(),
            'status' => 'ok',
            'stacktrace' => [],
        ];
    }

    public function fail(FailEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'time' => $event->getTime(),
            'status' => 'fail',
            'stacktrace' => $event->getFail()->getTrace(),
        ];
    }

    public function error(FailEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'time' => $event->getTime(),
            'status' => 'error',
            'stacktrace' => $event->getFail()->getTrace(),
        ];
    }

    public function all(PrintResultEvent $event): void
    {
        file_put_contents(
            $this->config['output-path'] . DIRECTORY_SEPARATOR . self::FILENAME,
            json_encode($this->data, JSON_THROW_ON_ERROR)
        );
    }

    private function getTestName(TestEvent $event): string
    {
        return Descriptor::getTestFullName($event->getTest());
    }

    private function getTestFilename(TestEvent $event): string
    {
        $test = new ReflectionObject($event->getTest());
        if ($test->hasProperty('testClass') && $test->hasProperty('testMethod')) {
            $class = $test->getProperty('testClass')->getValue($event->getTest());
            $method = $test->getProperty('testMethod')->getValue($event->getTest());

            $classReflection = new ReflectionClass($class);
            $methodReflection = $classReflection->getMethod($method);

            return $classReflection->getFileName() . ':' . $methodReflection->getStartLine();
        }
        return Descriptor::getTestFileName($event->getTest());
    }
}
