<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'debug:tail', description: 'Watch debug entries in real-time')]
final class DebugTailCommand extends Command
{
    public function __construct(
        private readonly CollectorRepositoryInterface $collectorRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Poll interval in seconds', '1')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Watch for new debug entries in real-time (like tail -f).

                Start watching:
                  <info>debug:tail</info>

                Custom poll interval (2 seconds):
                  <info>debug:tail --interval=2</info>

                Press Ctrl+C to stop.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $interval = max(1, (int) $input->getOption('interval'));
        $json = (bool) $input->getOption('json');

        $io->title('Watching debug entries (Ctrl+C to stop)');
        $io->text(sprintf('Poll interval: %ds', $interval));
        $io->newLine();

        $knownIds = $this->getEntryIds();

        while (true) {
            sleep($interval);

            $currentIds = $this->getEntryIds();
            $newIds = array_diff($currentIds, $knownIds);

            if ($newIds === []) {
                continue;
            }

            foreach ($newIds as $id) {
                $this->renderEntry($output, $io, $id, $json);
            }

            $knownIds = $currentIds;
        }
    }

    /** @return list<string> */
    private function getEntryIds(): array
    {
        $entries = $this->collectorRepository->getSummary();
        $ids = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['id']) && is_string($entry['id'])) {
                $ids[] = $entry['id'];
            }
        }
        return $ids;
    }

    private function renderEntry(OutputInterface $output, SymfonyStyle $io, string $id, bool $json): void
    {
        $summary = $this->collectorRepository->getSummary($id);

        if ($json) {
            $output->writeln(json_encode($summary, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            return;
        }

        $method = '—';
        $url = '—';
        $status = '—';
        $time = date('H:i:s');

        foreach (['request', 'web', 'command'] as $summaryKey) {
            if (isset($summary[$summaryKey]) && is_array($summary[$summaryKey])) {
                $method = (string) ($summary[$summaryKey]['method'] ?? $method);
                $url = (string) ($summary[$summaryKey]['url'] ?? $url);
                $status = (string) ($summary[$summaryKey]['responseStatusCode'] ?? $status);
                break;
            }
        }

        $collectors = $this->formatCollectors($summary);
        $hasException = isset($summary['exception']['class']);

        $statusColor = match (true) {
            $hasException => 'red',
            str_starts_with($status, '2') => 'green',
            str_starts_with($status, '3') => 'yellow',
            str_starts_with($status, '4'), str_starts_with($status, '5') => 'red',
            default => 'default',
        };

        $io->text(sprintf(
            '[%s] <fg=%s>%s</> %s %s %s',
            $time,
            $statusColor,
            $status,
            $method,
            $url,
            $collectors !== '' ? "({$collectors})" : '',
        ));
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

        if (isset($entry['exception']) && is_array($entry['exception']) && isset($entry['exception']['class'])) {
            $parts[] = 'exception';
        }

        return implode(', ', $parts);
    }
}
