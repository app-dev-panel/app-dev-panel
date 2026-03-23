<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use AppDevPanel\Kernel\StartupContext;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DebuggerTest extends TestCase
{
    public function testStartup(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('startup');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->once())->method('addCollector');

        $debugger = new Debugger($idGenerator, $storage, [$collector]);
        $debugger->startup(StartupContext::generic());
    }

    public function testStartupWithSkipCollect(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('startup');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->once())->method('addCollector');

        $debugger = new Debugger($idGenerator, $storage, [$collector], new DebuggerIgnoreConfig(requests: ['/test']));
        $debugger->startup(StartupContext::forRequest(new ServerRequest('GET', '/debug')));
    }

    public function testGetId(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $debugger = new Debugger($idGenerator, new MemoryStorage($idGenerator), []);

        $this->assertEquals($idGenerator->getId(), $debugger->getId());
    }

    public function testWithIgnoredRequests(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $debugger1 = new Debugger($idGenerator, new MemoryStorage($idGenerator), []);
        $debugger2 = $debugger1->withIgnoredRequests(['/test']);

        $this->assertNotSame($debugger1, $debugger2);
    }

    public function testIgnoreByHeader(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('flush');

        $debugger = new Debugger($idGenerator, $storage, [$collector]);
        $debugger->startup(StartupContext::forRequest(new ServerRequest('GET', '/test', ['X-Debug-Ignore' => 'true'])));
        $debugger->shutdown();
    }

    public function testWithIgnoredCommands(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $debugger1 = new Debugger($idGenerator, new MemoryStorage($idGenerator), []);
        $debugger2 = $debugger1->withIgnoredCommands(['command/test']);

        $this->assertNotSame($debugger1, $debugger2);
    }

    public function testIgnoreByEnv(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('flush');

        putenv('YII_DEBUG_IGNORE=true');
        $debugger = new Debugger($idGenerator, $storage, [$collector]);
        $debugger->startup(StartupContext::forCommand('command'));
        putenv('YII_DEBUG_IGNORE=false');
        $debugger->shutdown();
    }

    public function testShutdown(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->once())->method('flush');

        $debugger = new Debugger($idGenerator, $storage, [$collector]);
        $debugger->startup(StartupContext::forRequest(new ServerRequest('GET', '/test')));
        $debugger->shutdown();
        $debugger->shutdown();
        $debugger->shutdown();
    }

    public function testShutdownWithSkipRequestCollect(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('flush');

        $debugger = new Debugger($idGenerator, $storage, [$collector], new DebuggerIgnoreConfig(requests: ['/test']));
        $debugger->startup(StartupContext::forRequest(new ServerRequest('GET', '/test')));
        $debugger->shutdown();
    }

    #[DataProvider('dataShutdownWithSkipCommandCollect')]
    public function testShutdownWithSkipCommandCollect(array $ignoredCommands, ?string $ignoredCommand): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->never())->method('startup');
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('addCollector');
        $storage->expects($this->never())->method('flush');

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$collector],
            new DebuggerIgnoreConfig(commands: $ignoredCommands),
        );
        $debugger->startup(StartupContext::forCommand($ignoredCommand));
        $debugger->shutdown();
    }

    public static function dataShutdownWithSkipCommandCollect(): iterable
    {
        yield [
            ['app:ignored-command'],
            'app:ignored-command',
        ];
        yield [
            ['app:ignored-command1', 'app:ignored-command2'],
            'app:ignored-command2',
        ];
        yield [
            ['app:ignored-command'],
            null,
        ];
        yield [
            ['app:ignored-command'],
            '',
        ];
    }

    #[DataProvider('dataShutdownWithoutSkipCommandCollect')]
    public function testShutdownWithoutSkipCommandCollect(array $ignoredCommands, ?string $ignoredCommand): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('startup');
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->once())->method('addCollector');
        $storage->expects($this->once())->method('flush');

        $debugger = new Debugger(
            $idGenerator,
            $storage,
            [$collector],
            new DebuggerIgnoreConfig(commands: $ignoredCommands),
        );
        $debugger->startup(StartupContext::forCommand($ignoredCommand));
        $debugger->shutdown();
    }

    public static function dataShutdownWithoutSkipCommandCollect(): iterable
    {
        yield [
            [],
            'app:not-ignored-command',
        ];
        yield [
            ['app:ignored-command'],
            'app:not-ignored-command',
        ];
    }

    public function testStopSkipped(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->once())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('clear');
        $storage->expects($this->never())->method('flush');

        $debugger = new Debugger($idGenerator, $storage, [$collector]);
        $debugger->startup(StartupContext::forRequest(new ServerRequest('GET', '/test')));
        $debugger->stop();
        $debugger->stop();
    }

    public function testShutdownNotRegisteredWithoutStartup(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->expects($this->never())->method('startup');
        $collector->expects($this->never())->method('shutdown');
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects($this->never())->method('flush');

        // Just constructing should not register shutdown or activate
        new Debugger($idGenerator, $storage, [$collector]);

        // No assertion needed — mock expectations verify no methods were called
    }
}
