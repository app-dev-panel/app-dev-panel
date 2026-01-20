<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\Storage\StorageInterface;

#[AsCommand(
    name: 'debug:reset',
    description: 'Clear debug data',
)]
final class DebugResetCommand extends Command
{
    public const COMMAND_NAME = 'debug:reset';
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly Debugger $debugger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command clears debug storage data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->debugger->stop();
        $this->storage->clear();

        return ExitCode::OK;
    }
}
