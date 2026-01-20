<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\Output\ConsoleBufferedOutput;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;

final class CommandCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * Let -1 mean that it was not set during the process.
     */
    private const UNDEFINED_EXIT_CODE = -1;
    private array $commands = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector
    ) {
    }

    public function getCollected(): array
    {
        return $this->commands;
    }

    public function collect(ConsoleEvent|ConsoleErrorEvent|ConsoleTerminateEvent $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->timelineCollector->collect($this, spl_object_id($event));

        $command = $event->getCommand();

        if ($event instanceof ConsoleErrorEvent) {
            $this->commands[$event::class] = [
                'name' => $event->getInput()->getFirstArgument() ?? '',
                'command' => $command,
                'input' => $this->castInputToString($event->getInput()),
                'output' => $this->fetchOutput($event->getOutput()),
                'error' => $event->getError()->getMessage(),
                'exitCode' => $event->getExitCode(),
            ];

            return;
        }

        if ($event instanceof ConsoleTerminateEvent) {
            $this->commands[$event::class] = [
                'name' => $command?->getName() ?? $event->getInput()->getFirstArgument() ?? '',
                'command' => $command,
                'input' => $this->castInputToString($event->getInput()),
                'output' => $this->fetchOutput($event->getOutput()),
                'exitCode' => $event->getExitCode(),
            ];
            return;
        }

        $definition = $command?->getDefinition();
        $this->commands[$event::class] = [
            'name' => $command?->getName() ?? $event->getInput()->getFirstArgument() ?? '',
            'command' => $command,
            'input' => $this->castInputToString($event->getInput()),
            'output' => $this->fetchOutput($event->getOutput()),
            'arguments' => $definition?->getArguments() ?? [],
            'options' => $definition?->getOptions() ?? [],
        ];
    }

    public function getSummary(): array
    {
        if (empty($this->commands)) {
            return [];
        }

        $eventTypes = $this->getSupportedEvents();

        $commandEvent = null;
        foreach ($eventTypes as $eventType) {
            if (!array_key_exists($eventType, $this->commands)) {
                continue;
            }

            $commandEvent = $this->commands[$eventType];
            break;
        }

        if ($commandEvent === null) {
            return [];
        }

        return [
            'command' => [
                'name' => $commandEvent['name'],
                'class' => $commandEvent['command'] instanceof Command ? $commandEvent['command']::class : null,
                'input' => $commandEvent['input'],
                'exitCode' => $commandEvent['exitCode'] ?? self::UNDEFINED_EXIT_CODE,
            ],
        ];
    }

    private function reset(): void
    {
        $this->commands = [];
    }

    private function fetchOutput(OutputInterface $output): ?string
    {
        return $output instanceof ConsoleBufferedOutput ? $output->fetch() : null;
    }

    private function castInputToString(InputInterface $input): ?string
    {
        return method_exists($input, '__toString') ? $input->__toString() : null;
    }

    private function getSupportedEvents(): array
    {
        return [
            ConsoleErrorEvent::class,
            ConsoleTerminateEvent::class,
            ConsoleEvent::class,
        ];
    }
}
