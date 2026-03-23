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

#[AsCommand(name: 'debug:query', description: 'Query debug data: list entries, view by ID, filter by collector')]
final class DebugQueryCommand extends Command
{
    public function __construct(
        private readonly CollectorRepositoryInterface $collectorRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: list, view, collector', 'list')
            ->addArgument('id', InputArgument::OPTIONAL, 'Debug entry ID')
            ->addOption('collector', 'c', InputOption::VALUE_OPTIONAL, 'Collector class name to filter by')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit entries for list action', '20')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Query stored debug data from the CLI.

                List recent debug entries:
                  <info>debug:query list</info>
                  <info>debug:query list --limit=5</info>

                View full data for an entry:
                  <info>debug:query view <id></info>

                View specific collector data:
                  <info>debug:query view <id> --collector=AppDevPanel\\Kernel\\Collector\\LogCollector</info>

                Output raw JSON:
                  <info>debug:query list --json</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        if ($action !== 'list' && $action !== 'view') {
            $io->error(sprintf('Unknown action "%s". Available: list, view', $action));
            return Command::FAILURE;
        }

        return match ($action) {
            'list' => $this->listEntries($input, $output, $io),
            'view' => $this->viewEntry($input, $output, $io),
        };
    }

    private function listEntries(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $limit = (int) $input->getOption('limit');
        $entries = $this->collectorRepository->getSummary();
        $json = (bool) $input->getOption('json');

        if ($entries === []) {
            $io->info('No debug entries found.');
            return Command::SUCCESS;
        }

        $entries = array_slice($entries, 0, $limit);

        if ($json) {
            $output->writeln(json_encode($entries, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->title(sprintf('Debug Entries (showing %d)', count($entries)));

        $rows = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $rows[] = [
                (string) ($entry['id'] ?? '—'),
                $this->extractRequestInfo($entry, 'method', 'GET'),
                $this->extractRequestInfo($entry, 'url', '—'),
                $this->extractRequestInfo($entry, 'responseStatusCode', '—'),
                $this->formatCollectors($entry),
            ];
        }

        $io->table(['ID', 'Method', 'URL', 'Status', 'Collectors'], $rows);

        return Command::SUCCESS;
    }

    private function viewEntry(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $id = $input->getArgument('id');
        if (!is_string($id)) {
            $io->error('Entry ID is required for "view" action. Usage: debug:query view <id>');
            return Command::FAILURE;
        }

        $collectorClass = $input->getOption('collector');
        $json = (bool) $input->getOption('json');

        try {
            $data = $this->collectorRepository->getDetail($id);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (is_string($collectorClass)) {
            if (!array_key_exists($collectorClass, $data)) {
                $io->error(sprintf('Collector "%s" not found in entry "%s".', $collectorClass, $id));
                $io->text('Available collectors:');
                foreach (array_keys($data) as $key) {
                    $io->text(sprintf('  - %s', (string) $key));
                }
                return Command::FAILURE;
            }

            $collectorData = is_array($data[$collectorClass]) ? $data[$collectorClass] : [];

            if (!$json) {
                $io->title(sprintf('Collector: %s (Entry: %s)', $collectorClass, $id));
            }

            $this->writeJson($output, $collectorData);
            return Command::SUCCESS;
        }

        if ($json) {
            $this->writeJson($output, $data);
            return Command::SUCCESS;
        }

        $this->renderFullEntry($io, $output, $data, $id);

        return Command::SUCCESS;
    }

    private function renderFullEntry(SymfonyStyle $io, OutputInterface $output, array $data, string $id): void
    {
        $io->title(sprintf('Debug Entry: %s', $id));

        foreach ($data as $collector => $collectorData) {
            $io->section((string) $collector);
            if (is_array($collectorData) && $collectorData !== []) {
                $this->writeJson($output, $collectorData);
            } else {
                $io->text('(empty)');
            }
        }
    }

    private function writeJson(OutputInterface $output, array $data): void
    {
        $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function extractRequestInfo(array $entry, string $key, string $default): string
    {
        foreach (['request', 'web', 'command'] as $summaryKey) {
            if (
                array_key_exists($summaryKey, $entry)
                && is_array($entry[$summaryKey])
                && array_key_exists($key, $entry[$summaryKey])
            ) {
                return (string) $entry[$summaryKey][$key];
            }
        }

        return $default;
    }

    private function formatCollectors(array $entry): string
    {
        $parts = [];

        foreach (['logger' => 'logs', 'event' => 'events', 'timeline' => 'timeline'] as $key => $label) {
            $total = (int) ($entry[$key]['total'] ?? 0);
            if ($total > 0) {
                $parts[] = sprintf('%s:%d', $label, $total);
            }
        }

        if (
            array_key_exists('exception', $entry)
            && is_array($entry['exception'])
            && array_key_exists('class', $entry['exception'])
        ) {
            $parts[] = 'exception';
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }
}
