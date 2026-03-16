<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\Collector\SymfonyExceptionCollector;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\StartupContext;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class ConsoleSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ConsoleSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertArrayHasKey(ConsoleEvents::ERROR, $events);
        $this->assertArrayHasKey(ConsoleEvents::TERMINATE, $events);

        $this->assertSame(['onConsoleCommand', 1024], $events[ConsoleEvents::COMMAND]);
        $this->assertSame(['onConsoleError', 0], $events[ConsoleEvents::ERROR]);
        $this->assertSame(['onConsoleTerminate', -2048], $events[ConsoleEvents::TERMINATE]);
    }

    public function testOnConsoleCommandStartsDebugger(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $subscriber = new ConsoleSubscriber($debugger);

        $command = new Command('app:test');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new NullOutput());

        $subscriber->onConsoleCommand($event);

        $this->assertNotEmpty($debugger->getId());
    }

    public function testOnConsoleErrorCollectsException(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $timeline = new TimelineCollector();
        $exceptionCollector = new SymfonyExceptionCollector($timeline);
        $debugger = new Debugger($idGenerator, $storage, [$exceptionCollector, $timeline]);
        $debugger->startup(StartupContext::forCommand('test'));

        $subscriber = new ConsoleSubscriber($debugger, exceptionCollector: $exceptionCollector);

        $error = new \RuntimeException('Command failed');
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $event = new ConsoleErrorEvent($input, $output, $error);

        $subscriber->onConsoleError($event);

        $data = $exceptionCollector->getCollected();
        $this->assertCount(1, $data);
        $this->assertSame(\RuntimeException::class, $data[0]['class']);
        $this->assertSame('Command failed', $data[0]['message']);
    }

    public function testOnConsoleTerminateCallsShutdown(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->createMock(\AppDevPanel\Kernel\Storage\StorageInterface::class);
        $storage->expects($this->once())->method('flush');
        $debugger = new Debugger($idGenerator, $storage, []);
        $debugger->startup(StartupContext::forCommand('test'));

        $subscriber = new ConsoleSubscriber($debugger);

        $command = new Command('app:test');
        $event = new ConsoleTerminateEvent($command, new ArrayInput([]), new NullOutput(), 0);

        $subscriber->onConsoleTerminate($event);
    }

    public function testFullConsoleLifecycle(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);

        $subscriber = new ConsoleSubscriber($debugger);

        $command = new Command('app:migrate');
        $input = new ArrayInput([]);
        $output = new NullOutput();

        // 1. Command start
        $subscriber->onConsoleCommand(new ConsoleCommandEvent($command, $input, $output));

        // 2. Command terminate
        $subscriber->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, 0));

        // No exceptions means lifecycle completed successfully
        $this->assertTrue(true);
    }

    public function testNullCollectorsAreHandled(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new MemoryStorage($idGenerator);
        $debugger = new Debugger($idGenerator, $storage, []);
        $debugger->startup(StartupContext::forCommand('test'));

        // Subscriber with no optional collectors — should not throw
        $subscriber = new ConsoleSubscriber($debugger);

        $command = new Command('app:test');
        $input = new ArrayInput([]);
        $output = new NullOutput();

        $subscriber->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, 0));

        $this->assertTrue(true);
    }
}
