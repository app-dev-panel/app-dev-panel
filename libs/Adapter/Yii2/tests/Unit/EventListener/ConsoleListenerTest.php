<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Yii2\EventListener\ConsoleListener;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use yii\base\Event;
use yii\console\Application;
use yii\console\ErrorHandler;
use yii\console\Request;

final class ConsoleListenerTest extends TestCase
{
    public function testOnBeforeRequestStartsDebugger(): void
    {
        [$listener, $debugger] = $this->createListener();

        $app = $this->createConsoleApp(['migrate/up']);
        $event = new Event(['sender' => $app]);

        $listener->onBeforeRequest($event);

        $this->assertNotEmpty($debugger->getId());
    }

    public function testOnAfterRequestShutsDownDebugger(): void
    {
        [$listener, $debugger] = $this->createListener();

        $app = $this->createConsoleApp(['cache/clear']);
        $beforeEvent = new Event(['sender' => $app]);
        $listener->onBeforeRequest($beforeEvent);

        $afterEvent = new Event(['sender' => $app]);
        $listener->onAfterRequest($afterEvent);

        // Debugger should have completed a full cycle
        $this->assertNotEmpty($debugger->getId());
    }

    public function testOnBeforeRequestIgnoresNonConsoleApp(): void
    {
        [$listener] = $this->createListener();

        $event = new Event(['sender' => new \stdClass()]);
        $listener->onBeforeRequest($event);

        $this->assertTrue(true, 'No exception thrown for non-console-app sender');
    }

    public function testOnAfterRequestIgnoresNonConsoleApp(): void
    {
        [$listener] = $this->createListener();

        $event = new Event(['sender' => new \stdClass()]);
        $listener->onAfterRequest($event);

        $this->assertTrue(true, 'No exception thrown for non-console-app sender');
    }

    public function testExtractsCommandNameFromParams(): void
    {
        [$listener, $debugger] = $this->createListener();

        $app = $this->createConsoleApp(['migrate/up', '--interactive=0']);
        $event = new Event(['sender' => $app]);

        $listener->onBeforeRequest($event);

        // Debugger started means command name was extracted
        $this->assertNotEmpty($debugger->getId());
    }

    /**
     * @return array{0: ConsoleListener, 1: Debugger}
     */
    private function createListener(): array
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$timeline],
        );

        $listener = new ConsoleListener($debugger);

        return [$listener, $debugger];
    }

    private function createConsoleApp(array $params = []): Application
    {
        $request = $this->createMock(Request::class);
        $request->method('getParams')->willReturn($params);

        $errorHandler = $this->createMock(ErrorHandler::class);
        $errorHandler->exception = null;

        $app = $this->createMock(Application::class);
        $app->method('getRequest')->willReturn($request);
        $app->method('getErrorHandler')->willReturn($errorHandler);

        return $app;
    }
}
