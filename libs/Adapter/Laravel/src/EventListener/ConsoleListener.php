<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Listens for Laravel console events and maps them to the ADP Debugger lifecycle.
 *
 * CommandStarting → Debugger::startup()
 * CommandFinished → Debugger::shutdown()
 */
final class ConsoleListener
{
    /** @var \Closure(): Debugger */
    private \Closure $debuggerFactory;

    /** @var \Closure(): CommandCollector */
    private \Closure $commandCollectorFactory;

    /** @var \Closure(): ConsoleAppInfoCollector */
    private \Closure $appInfoCollectorFactory;

    /** @var \Closure(): ExceptionCollector */
    private \Closure $exceptionCollectorFactory;

    /** @var \Closure(): EnvironmentCollector */
    private \Closure $environmentCollectorFactory;

    /**
     * @param \Closure(): Debugger $debuggerFactory
     * @param \Closure(): CommandCollector $commandCollectorFactory
     * @param \Closure(): ConsoleAppInfoCollector $appInfoCollectorFactory
     * @param \Closure(): ExceptionCollector $exceptionCollectorFactory
     * @param \Closure(): EnvironmentCollector $environmentCollectorFactory
     */
    public function __construct(
        \Closure $debuggerFactory,
        \Closure $commandCollectorFactory,
        \Closure $appInfoCollectorFactory,
        \Closure $exceptionCollectorFactory,
        \Closure $environmentCollectorFactory,
    ) {
        $this->debuggerFactory = $debuggerFactory;
        $this->commandCollectorFactory = $commandCollectorFactory;
        $this->appInfoCollectorFactory = $appInfoCollectorFactory;
        $this->exceptionCollectorFactory = $exceptionCollectorFactory;
        $this->environmentCollectorFactory = $environmentCollectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(CommandStarting::class, function (CommandStarting $event): void {
            ($this->debuggerFactory)()->startup(StartupContext::forCommand($event->command));
            ($this->environmentCollectorFactory)()->collectFromGlobals();
        });

        $events->listen(CommandFinished::class, function (CommandFinished $event): void {
            ($this->debuggerFactory)()->shutdown();
        });
    }
}
