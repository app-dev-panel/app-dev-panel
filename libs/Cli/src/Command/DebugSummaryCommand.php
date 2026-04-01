<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'debug:summary', description: 'Show brief summary of a debug entry')]
final class DebugSummaryCommand extends Command
{
    public function __construct(
        private readonly CollectorRepositoryInterface $collectorRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Debug entry ID')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Show brief summary of a debug entry: method, URL, status, timing, memory, exception info.

                View summary:
                  <info>debug:summary <id></info>
                  <info>debug:summary <id> --json</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (string) $input->getArgument('id');
        $json = (bool) $input->getOption('json');

        try {
            $data = $this->collectorRepository->getSummary($id);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($json) {
            $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->title(sprintf('Debug Entry Summary: %s', $id));

        $this->renderRequestInfo($io, $data);
        $this->renderTimingInfo($io, $data);
        $this->renderCollectorSummary($io, $data);
        $this->renderException($io, $data);

        return Command::SUCCESS;
    }

    private function renderRequestInfo(SymfonyStyle $io, array $data): void
    {
        $requestData = null;
        $type = 'unknown';

        foreach (['request' => 'web', 'web' => 'web', 'command' => 'console'] as $key => $label) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $requestData = $data[$key];
                $type = $label;
                break;
            }
        }

        if ($requestData === null) {
            return;
        }

        $rows = [
            ['Type', $type],
            ['Method', (string) ($requestData['method'] ?? '—')],
            ['URL', (string) ($requestData['url'] ?? '—')],
            ['Status', (string) ($requestData['responseStatusCode'] ?? '—')],
        ];

        $io->table(['', ''], $rows);
    }

    private function renderTimingInfo(SymfonyStyle $io, array $data): void
    {
        $timing = $data['timeline'] ?? $data['web'] ?? $data['request'] ?? [];
        if (!is_array($timing)) {
            return;
        }

        $rows = [];

        if (isset($timing['duration'])) {
            $rows[] = ['Duration', sprintf('%.2f ms', (float) $timing['duration'])];
        }

        if (isset($timing['memory'])) {
            $memory = (int) $timing['memory'];
            $rows[] = ['Memory', $this->formatBytes($memory)];
        }

        if (isset($timing['memoryPeak'])) {
            $memoryPeak = (int) $timing['memoryPeak'];
            $rows[] = ['Memory Peak', $this->formatBytes($memoryPeak)];
        }

        if ($rows !== []) {
            $io->section('Timing');
            $io->table(['', ''], $rows);
        }
    }

    private function renderCollectorSummary(SymfonyStyle $io, array $data): void
    {
        $rows = [];

        foreach (['logger' => 'Log entries', 'event' => 'Events', 'timeline' => 'Timeline entries'] as $key => $label) {
            $total = (int) ($data[$key]['total'] ?? 0);
            if ($total > 0) {
                $rows[] = [$label, (string) $total];
            }
        }

        if (isset($data['db']['total'])) {
            $rows[] = ['DB Queries', (string) $data['db']['total']];
        }

        if (isset($data['cache']['total'])) {
            $rows[] = ['Cache Operations', (string) $data['cache']['total']];
        }

        if (isset($data['mailer']['total'])) {
            $rows[] = ['Emails', (string) $data['mailer']['total']];
        }

        if (isset($data['queue']['total'])) {
            $rows[] = ['Queue Jobs', (string) $data['queue']['total']];
        }

        if ($rows !== []) {
            $io->section('Collectors');
            $io->table(['Collector', 'Count'], $rows);
        }
    }

    private function renderException(SymfonyStyle $io, array $data): void
    {
        if (!isset($data['exception']) || !is_array($data['exception']) || !isset($data['exception']['class'])) {
            return;
        }

        $exception = $data['exception'];
        $io->section('Exception');
        $io->error(sprintf(
            '%s: %s',
            (string) ($exception['class'] ?? 'Unknown'),
            (string) ($exception['message'] ?? ''),
        ));

        if (isset($exception['file'])) {
            $io->text(sprintf('at %s:%s', (string) $exception['file'], (string) ($exception['line'] ?? '?')));
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return sprintf('%d B', $bytes);
        }
        if ($bytes < (1024 * 1024)) {
            return sprintf('%.1f KB', $bytes / 1024);
        }

        return sprintf('%.1f MB', $bytes / (1024 * 1024));
    }
}
