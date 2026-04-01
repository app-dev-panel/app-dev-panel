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

#[AsCommand(name: 'debug:dump', description: 'View dumped objects for a debug entry')]
final class DebugDumpCommand extends Command
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
            ->addArgument('objectId', InputArgument::OPTIONAL, 'Specific object ID')
            ->addOption('collector', 'c', InputOption::VALUE_OPTIONAL, 'Filter by collector class')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                View dumped objects for a debug entry.

                List all dumped objects:
                  <info>debug:dump <id></info>

                View specific object:
                  <info>debug:dump <id> <objectId></info>

                Filter by collector:
                  <info>debug:dump <id> --collector=AppDevPanel\\Kernel\\Collector\\LogCollector</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (string) $input->getArgument('id');
        $objectId = $input->getArgument('objectId');
        $collector = $input->getOption('collector');
        $json = (bool) $input->getOption('json');

        if (is_string($objectId) && $objectId !== '') {
            return $this->viewObject($output, $io, $id, $objectId, $json);
        }

        return $this->viewDump($output, $io, $id, is_string($collector) ? $collector : null, $json);
    }

    private function viewDump(
        OutputInterface $output,
        SymfonyStyle $io,
        string $id,
        ?string $collector,
        bool $json,
    ): int {
        try {
            $data = $this->collectorRepository->getDumpObject($id);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($collector !== null) {
            if (!array_key_exists($collector, $data)) {
                $io->error(sprintf('Collector "%s" not found in dump for entry "%s".', $collector, $id));
                $io->text('Available collectors:');
                foreach (array_keys($data) as $key) {
                    $io->text(sprintf('  - %s', (string) $key));
                }
                return Command::FAILURE;
            }
            $data = [$collector => $data[$collector]];
        }

        if ($json) {
            $this->writeJson($output, $data);
            return Command::SUCCESS;
        }

        $io->title(sprintf('Object Dump: %s', $id));

        if ($data === []) {
            $io->info('No dumped objects found.');
            return Command::SUCCESS;
        }

        foreach ($data as $name => $value) {
            $io->section((string) $name);
            if (is_array($value) && $value !== []) {
                $this->writeJson($output, $value);
            } else {
                $io->text('(empty)');
            }
        }

        return Command::SUCCESS;
    }

    private function viewObject(
        OutputInterface $output,
        SymfonyStyle $io,
        string $id,
        string $objectId,
        bool $json,
    ): int {
        try {
            $data = $this->collectorRepository->getObject($id, $objectId);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($data === null) {
            $io->error(sprintf('Object "%s" not found in entry "%s".', $objectId, $id));
            return Command::FAILURE;
        }

        $result = [
            'class' => $data[0],
            'value' => $data[1],
        ];

        if ($json) {
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        $io->title(sprintf('Object: %s', $data[0]));
        $output->writeln(json_encode($data[1], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }

    private function writeJson(OutputInterface $output, array $data): void
    {
        $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
