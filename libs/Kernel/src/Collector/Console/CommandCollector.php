<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Console;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

final class CommandCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * Let -1 mean that it was not set during the process.
     */
    private const UNDEFINED_EXIT_CODE = -1;

    private array $commands = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

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

        $this->commands[$event::class] = match (true) {
            $event instanceof ConsoleErrorEvent => $this->collectErrorEvent($event),
            $event instanceof ConsoleTerminateEvent => $this->collectTerminateEvent($event),
            default => $this->collectGenericEvent($event),
        };
    }

    /**
     * Framework-agnostic entry point for collecting command data.
     *
     * Use this when the framework doesn't use Symfony Console events (e.g. Yii 2).
     *
     * @param array{name: string, input: ?string, exitCode?: int, error?: string, command?: object|null, output?: ?string} $data
     */
    public function collectCommandData(array $data): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->timelineCollector->collect($this, crc32($data['name'] ?? 'unknown'));

        $this->commands['command'] = [
            'name' => $data['name'] ?? '',
            'command' => $data['command'] ?? null,
            'input' => $data['input'] ?? null,
            'output' => $data['output'] ?? null,
            'exitCode' => $data['exitCode'] ?? self::UNDEFINED_EXIT_CODE,
            ...(isset($data['error']) ? ['error' => $data['error']] : []),
        ];
    }

    public function getSummary(): array
    {
        if ($this->commands === []) {
            return [];
        }

        $commandEvent =
            $this->commands[ConsoleErrorEvent::class] ?? $this->commands[ConsoleTerminateEvent::class] ?? $this->commands[ConsoleEvent::class] ?? $this->commands['command']
                ?? null;

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

    private function collectErrorEvent(ConsoleErrorEvent $event): array
    {
        return [
            ...$this->buildBaseCommandData($event),
            'error' => $event->getError()->getMessage(),
            'exitCode' => $event->getExitCode(),
        ];
    }

    private function collectTerminateEvent(ConsoleTerminateEvent $event): array
    {
        return [
            ...$this->buildBaseCommandData($event),
            'exitCode' => $event->getExitCode(),
        ];
    }

    private function collectGenericEvent(ConsoleEvent $event): array
    {
        $command = $event->getCommand();
        $definition = $command?->getDefinition();

        return [
            ...$this->buildBaseCommandData($event),
            'arguments' => $definition?->getArguments() ?? [],
            'options' => $definition?->getOptions() ?? [],
        ];
    }

    private function buildBaseCommandData(ConsoleEvent $event): array
    {
        $command = $event->getCommand();
        $input = $event->getInput();
        return [
            'name' => $command?->getName() ?? $input->getFirstArgument() ?? '',
            'command' => $command,
            'input' => method_exists($input, '__toString') ? $input->__toString() : null,
            'output' => method_exists($event->getOutput(), 'fetch') ? $event->getOutput()->fetch() : null,
        ];
    }

    private function reset(): void
    {
        $this->commands = [];
    }
}
