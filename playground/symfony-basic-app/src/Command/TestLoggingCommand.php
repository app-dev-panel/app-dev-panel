<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-logging', description: 'Test ADP log collection')]
final class TestLoggingCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Test log message from console command');
        $this->logger->warning('This is a warning log', ['key' => 'value']);
        $this->logger->error('This is an error log', ['code' => 500]);

        $output->writeln('Logged 3 messages (info, warning, error)');

        return Command::SUCCESS;
    }
}
