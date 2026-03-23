<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Command;

use AppDevPanel\Testing\Runner\FixtureResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FixtureResultRenderer
{
    /**
     * @param list<FixtureResult> $results
     */
    public function render(SymfonyStyle $io, OutputInterface $output, array $results): int
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
                $this->renderPassedResult($io, $result);
            } else {
                $failed++;
                $this->renderFailedResult($io, $result);
            }

            $this->renderVerboseDetails($io, $output, $result);
        }

        $this->renderSummary($io, $passed, $failed, $skipped, count($results));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function renderPassedResult(SymfonyStyle $io, FixtureResult $result): void
    {
        $io->text(sprintf('  <fg=green>PASS</> %s', $result->fixture->name));
    }

    private function renderFailedResult(SymfonyStyle $io, FixtureResult $result): void
    {
        $io->text(sprintf('  <fg=red>FAIL</> %s', $result->fixture->name));

        foreach ($result->assertions as $assertion) {
            if ($assertion->passed) {
                continue;
            }

            $io->text(sprintf('       <fg=red>✗</> %s', $assertion->message));
        }
    }

    private function renderVerboseDetails(SymfonyStyle $io, OutputInterface $output, FixtureResult $result): void
    {
        if (!$output->isVerbose()) {
            return;
        }

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

    private function renderSummary(SymfonyStyle $io, int $passed, int $failed, int $skipped, int $total): void
    {
        $io->newLine();
        $io->text(sprintf(
            'Results: <fg=green>%d passed</>, <fg=red>%d failed</>, <fg=yellow>%d skipped</>, %d total',
            $passed,
            $failed,
            $skipped,
            $total,
        ));
    }
}
