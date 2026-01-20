<?php

declare(strict_types=1);
declare(ticks=1);

namespace Yiisoft\Yii\Debug\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Debug\DebugServer\Connection;

final class DebugServerBroadcastCommand extends Command
{
    public const COMMAND_NAME = 'dev:broadcast';
    protected static $defaultName = self::COMMAND_NAME;

    protected static $defaultDescription = 'Runs PHP built-in web server';

    public function configure(): void
    {
        $this
            ->setHelp(
                'Broadcasts a message to all connected clients.'
            )
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'A text to broadcast', 'Test message')
            ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'It is only used for testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Yii3 Debug Server');
        $io->writeln('https://yiiframework.com' . "\n");

        $env = $input->getOption('env');
        if ($env === 'test') {
            return ExitCode::OK;
        }

        $socket = Connection::create();
        if (\function_exists('pcntl_signal')) {
            $io->success('Quit the server with CTRL-C or COMMAND-C.');

            \pcntl_signal(\SIGINT, static function () use ($socket): void {
                $socket->close();
                exit(1);
            });
        }

        $data = $input->getOption('message');
        $socket->broadcast(Connection::MESSAGE_TYPE_LOGGER, $data);
        $socket->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, VarDumper::create(['$data' => $data])->asJson(false));

        return ExitCode::OK;
    }
}
