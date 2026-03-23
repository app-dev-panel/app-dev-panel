<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Command;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use AppDevPanel\Testing\Runner\FixtureRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'debug:fixtures', description: 'Run ADP test fixtures against a playground')]
final class DebugFixturesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('base-url', InputArgument::REQUIRED, 'Playground base URL (e.g., http://localhost:8080)')
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_OPTIONAL,
                'Run only fixtures with this tag (core, web, error, advanced)',
            )
            ->addOption('fixture', 's', InputOption::VALUE_OPTIONAL, 'Run a single fixture by name')
            ->addOption('retry-delay', null, InputOption::VALUE_OPTIONAL, 'Delay between retries in ms', '200')
            ->addOption('max-retries', null, InputOption::VALUE_OPTIONAL, 'Max retries for debug data fetch', '10')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List available fixtures without running them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = (string) $input->getArgument('base-url');

        if ($input->getOption('list')) {
            return $this->listFixtures($io);
        }

        $fixtures = $this->resolveFixtures($input, $io);
        if ($fixtures === null) {
            return Command::FAILURE;
        }
        if ($fixtures === []) {
            $io->warning('No fixtures to run.');
            return Command::SUCCESS;
        }

        $io->title(sprintf('ADP Test Fixtures — %s', $baseUrl));
        $io->text(sprintf('Running %d fixture(s)...', count($fixtures)));
        $io->newLine();

        $runner = new FixtureRunner(
            $baseUrl,
            (int) $input->getOption('retry-delay'),
            (int) $input->getOption('max-retries'),
        );

        $results = $runner->runAll($fixtures);

        $renderer = new FixtureResultRenderer();

        return $renderer->render($io, $output, $results);
    }

    private function listFixtures(SymfonyStyle $io): int
    {
        $io->title('Available Test Fixtures');

        foreach (FixtureRegistry::tags() as $tag) {
            $fixtures = FixtureRegistry::byTag($tag);
            $io->section(sprintf('Tag: %s (%d fixtures)', $tag, count($fixtures)));

            $rows = [];
            foreach ($fixtures as $fixture) {
                $collectors = implode(', ', array_keys($fixture->expectations));
                $rows[] = [$fixture->name, $fixture->method . ' ' . $fixture->endpoint, $collectors];
            }

            $io->table(['Name', 'Endpoint', 'Expected Collectors'], $rows);
        }

        return Command::SUCCESS;
    }

    private function resolveFixtures(InputInterface $input, SymfonyStyle $io): ?array
    {
        $fixtureName = $input->getOption('fixture');
        $tag = $input->getOption('tag');

        if (is_string($fixtureName)) {
            $all = FixtureRegistry::all();
            foreach ($all as $fixture) {
                if ($fixture->name === $fixtureName) {
                    return [$fixture];
                }
            }
            $io->error(sprintf('Fixture "%s" not found.', $fixtureName));
            return null;
        }

        if (is_string($tag)) {
            $fixtures = FixtureRegistry::byTag($tag);
            if ($fixtures === []) {
                $io->error(sprintf(
                    'No fixtures found for tag "%s". Available tags: %s',
                    $tag,
                    implode(', ', FixtureRegistry::tags()),
                ));
                return null;
            }
            return $fixtures;
        }

        return FixtureRegistry::all();
    }
}
