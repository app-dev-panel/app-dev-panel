<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Command;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use AppDevPanel\Testing\Runner\FixtureResult;
use AppDevPanel\Testing\Runner\FixtureRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Run ADP test fixtures against a live playground instance.
 *
 * Usage:
 *   debug:fixtures http://localhost:8080
 *   debug:fixtures http://localhost:8080 --tag=core
 *   debug:fixtures http://localhost:8080 --fixture=logs:basic
 */
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

        return $this->renderResults($io, $output, $results);
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
            $io->error(sprintf('Fixture "%s" not found.', (string) $fixtureName));
            return null;
        }

        if (is_string($tag)) {
            $fixtures = FixtureRegistry::byTag($tag);
            if ($fixtures === []) {
                $io->error(sprintf(
                    'No fixtures found for tag "%s". Available tags: %s',
                    (string) $tag,
                    implode(', ', FixtureRegistry::tags()),
                ));
                return null;
            }
            return $fixtures;
        }

        return FixtureRegistry::all();
    }

    /**
     * @param list<FixtureResult> $results
     */
    private function renderResults(SymfonyStyle $io, OutputInterface $output, array $results): int
    {
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $result) {
            if ($result->error !== null) {
                $skipped++;
                $io->text(sprintf('  <fg=yellow>SKIP</> %s — %s', $result->fixture->name, $result->error));
                continue;
            }

            if ($result->passed) {
                $passed++;
                $io->text(sprintf('  <fg=green>PASS</> %s', $result->fixture->name));
            } else {
                $failed++;
                $io->text(sprintf('  <fg=red>FAIL</> %s', $result->fixture->name));

                foreach ($result->assertions as $assertion) {
                    if ($assertion->passed) {
                        continue;
                    }

                    $io->text(sprintf('       <fg=red>✗</> %s', $assertion->message));
                }
            }

            // Show assertions in verbose mode
            if ($output->isVerbose()) {
                foreach ($result->assertions as $assertion) {
                    if (!$assertion->passed) {
                        continue;
                    }

                    $io->text(sprintf('       <fg=green>✓</> %s', $assertion->message));
                }
                if ($result->debugId !== null) {
                    $io->text(sprintf('       Debug ID: %s', $result->debugId));
                }
            }
        }

        $io->newLine();
        $io->text(sprintf(
            'Results: <fg=green>%d passed</>, <fg=red>%d failed</>, <fg=yellow>%d skipped</>, %d total',
            $passed,
            $failed,
            $skipped,
            count($results),
        ));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
