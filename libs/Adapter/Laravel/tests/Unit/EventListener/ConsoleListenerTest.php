<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\ConsoleListener;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class ConsoleListenerTest extends TestCase
{
    public function testRegistersTwoEventListeners(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = $this->createConsoleListener();
        $listener->register($dispatcher);

        $this->assertCount(2, $registeredListeners);
        $this->assertArrayHasKey(CommandStarting::class, $registeredListeners);
        $this->assertArrayHasKey(CommandFinished::class, $registeredListeners);
    }

    public function testCommandStartingTriggersDebuggerStartup(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $debugger = new Debugger($idGenerator, $storage, [$timeline]);

        $listener = $this->createConsoleListenerWithDebugger($debugger);
        $listener->register($dispatcher);

        $event = new CommandStarting('test:command', new ArrayInput([]), new NullOutput());
        $registeredListeners[CommandStarting::class]($event);

        $this->assertNotEmpty($debugger->getId());
    }

    public function testCommandFinishedTriggersDebuggerShutdown(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $debugger = new Debugger($idGenerator, $storage, [$timeline]);

        $listener = $this->createConsoleListenerWithDebugger($debugger);
        $listener->register($dispatcher);

        // Start first
        $startEvent = new CommandStarting('test:command', new ArrayInput([]), new NullOutput());
        $registeredListeners[CommandStarting::class]($startEvent);

        // Then finish
        $finishEvent = new CommandFinished('test:command', new ArrayInput([]), new NullOutput(), 0);
        $registeredListeners[CommandFinished::class]($finishEvent);

        // After shutdown, storage should have data
        $summaries = $storage->read($storage::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries);
    }

    private function createConsoleListener(): ConsoleListener
    {
        $timeline = new TimelineCollector();
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, [$timeline]);

        return $this->createConsoleListenerWithDebugger($debugger);
    }

    private function createConsoleListenerWithDebugger(Debugger $debugger): ConsoleListener
    {
        $timeline = new TimelineCollector();

        return new ConsoleListener(
            fn() => $debugger,
            fn() => new CommandCollector($timeline),
            fn() => new ConsoleAppInfoCollector($timeline, 'Laravel'),
            fn() => new ExceptionCollector($timeline),
            fn() => new EnvironmentCollector(),
        );
    }
}
