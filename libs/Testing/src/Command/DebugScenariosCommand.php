<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Command;

use AppDevPanel\Testing\Runner\ScenarioResult;
use AppDevPanel\Testing\Runner\ScenarioRunner;
use AppDevPanel\Testing\Scenario\ScenarioRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Run ADP test scenarios against a live playground instance.
 *
 * Usage:
 *   debug:scenarios http://localhost:8080
 *   debug:scenarios http://localhost:8080 --tag=core
 *   debug:scenarios http://localhost:8080 --scenario=logs:basic
 */
#[AsCommand(name: 'debug:scenarios', description: 'Run ADP test scenarios against a playground')]
final class DebugScenariosCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('base-url', InputArgument::REQUIRED, 'Playground base URL (e.g., http://localhost:8080)')
            ->addOption('tag', 't', InputOption::VALUE_OPTIONAL, 'Run only scenarios with this tag (core, web, error, advanced)')
            ->addOption('scenario', 's', InputOption::VALUE_OPTIONAL, 'Run a single scenario by name')
            ->addOption('retry-delay', null, InputOption::VALUE_OPTIONAL, 'Delay between retries in ms', '200')
            ->addOption('max-retries', null, InputOption::VALUE_OPTIONAL, 'Max retries for debug data fetch', '10')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List available scenarios without running them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = $input->getArgument('base-url');

        if ($input->getOption('list')) {
            return $this->listScenarios($io);
        }

        $scenarios = $this->resolveScenarios($input, $io);
        if ($scenarios === null) {
            return Command::FAILURE;
        }
        if ($scenarios === []) {
            $io->warning('No scenarios to run.');
            return Command::SUCCESS;
        }

        $io->title(sprintf('ADP Test Scenarios — %s', $baseUrl));
        $io->text(sprintf('Running %d scenario(s)...', count($scenarios)));
        $io->newLine();

        $runner = new ScenarioRunner(
            $baseUrl,
            (int) $input->getOption('retry-delay'),
            (int) $input->getOption('max-retries'),
        );

        $results = $runner->runAll($scenarios);

        return $this->renderResults($io, $output, $results);
    }

    private function listScenarios(SymfonyStyle $io): int
    {
        $io->title('Available Test Scenarios');

        foreach (ScenarioRegistry::tags() as $tag) {
            $scenarios = ScenarioRegistry::byTag($tag);
            $io->section(sprintf('Tag: %s (%d scenarios)', $tag, count($scenarios)));

            $rows = [];
            foreach ($scenarios as $scenario) {
                $collectors = implode(', ', array_keys($scenario->expectations));
                $rows[] = [$scenario->name, $scenario->method . ' ' . $scenario->endpoint, $collectors];
            }

            $io->table(['Name', 'Endpoint', 'Expected Collectors'], $rows);
        }

        return Command::SUCCESS;
    }

    private function resolveScenarios(InputInterface $input, SymfonyStyle $io): ?array
    {
        $scenarioName = $input->getOption('scenario');
        $tag = $input->getOption('tag');

        if ($scenarioName !== null) {
            $all = ScenarioRegistry::all();
            foreach ($all as $scenario) {
                if ($scenario->name === $scenarioName) {
                    return [$scenario];
                }
            }
            $io->error(sprintf('Scenario "%s" not found.', $scenarioName));
            return null;
        }

        if ($tag !== null) {
            $scenarios = ScenarioRegistry::byTag($tag);
            if ($scenarios === []) {
                $io->error(sprintf('No scenarios found for tag "%s". Available tags: %s', $tag, implode(', ', ScenarioRegistry::tags())));
                return null;
            }
            return $scenarios;
        }

        return ScenarioRegistry::all();
    }

    /**
     * @param list<ScenarioResult> $results
     */
    private function renderResults(SymfonyStyle $io, OutputInterface $output, array $results): int
    {
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $result) {
            if ($result->error !== null) {
                $skipped++;
                $io->text(sprintf('  <fg=yellow>SKIP</> %s — %s', $result->scenario->name, $result->error));
                continue;
            }

            if ($result->passed) {
                $passed++;
                $io->text(sprintf('  <fg=green>PASS</> %s', $result->scenario->name));
            } else {
                $failed++;
                $io->text(sprintf('  <fg=red>FAIL</> %s', $result->scenario->name));

                foreach ($result->assertions as $assertion) {
                    if (!$assertion->passed) {
                        $io->text(sprintf('       <fg=red>✗</> %s', $assertion->message));
                    }
                }
            }

            // Show assertions in verbose mode
            if ($output->isVerbose()) {
                foreach ($result->assertions as $assertion) {
                    if ($assertion->passed) {
                        $io->text(sprintf('       <fg=green>✓</> %s', $assertion->message));
                    }
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
