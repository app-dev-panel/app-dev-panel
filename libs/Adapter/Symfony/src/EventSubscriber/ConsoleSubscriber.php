<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps Symfony Console events to the ADP Debugger lifecycle.
 *
 * console.command   → Debugger::startup() + CommandCollector
 * console.error     → ExceptionCollector + CommandCollector
 * console.terminate → CommandCollector + Debugger::shutdown()
 */
final class ConsoleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Debugger $debugger,
        private readonly ?CommandCollector $commandCollector = null,
        private readonly ?ConsoleAppInfoCollector $consoleAppInfoCollector = null,
        private readonly ?ExceptionCollector $exceptionCollector = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 1024],
            ConsoleEvents::ERROR => ['onConsoleError', 0],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -2048],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $commandName = $event->getCommand()?->getName();

        $this->debugger->startup(StartupContext::forCommand($commandName));

        $this->consoleAppInfoCollector?->collect($event);
        $this->commandCollector?->collect($event);
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->exceptionCollector?->collect($event->getError());
        $this->consoleAppInfoCollector?->collect($event);
        $this->commandCollector?->collect($event);
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->consoleAppInfoCollector?->collect($event);
        $this->commandCollector?->collect($event);
        $this->debugger->shutdown();
    }
}
