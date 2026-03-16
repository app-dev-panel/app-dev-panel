<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'dev:broadcast', description: 'Broadcast test messages to debug server clients')]
final class DebugServerBroadcastCommand extends Command
{
    public const COMMAND_NAME = 'dev:broadcast';

    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setHelp('Broadcasts a message to all connected clients.')
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'A text to broadcast', 'Test message')
            ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'It is only used for testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ADP Debug Server');

        $env = $input->getOption('env');
        if ($env === 'test') {
            return ExitCode::OK;
        }

        $broadcaster = new Broadcaster();
        /** @var string $data */
        $data = $input->getOption('message');

        $this->logger->info('Starting broadcast.', ['message' => $data]);

        $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, $data);
        $broadcaster->broadcast(
            Connection::MESSAGE_TYPE_VAR_DUMPER,
            VarDumper::create(['$data' => $data])->asJson(false),
        );

        $this->logger->info('Broadcast complete.', ['message' => $data]);

        return ExitCode::OK;
    }
}
