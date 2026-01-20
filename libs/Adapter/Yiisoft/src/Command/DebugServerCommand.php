<?php

declare(strict_types=1);
declare(ticks=1);

namespace Yiisoft\Yii\Debug\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Debug\DebugServer\Connection;

final class DebugServerCommand extends Command
{
    public const COMMAND_NAME = 'dev';
    protected static $defaultName = self::COMMAND_NAME;

    protected static $defaultDescription = 'Runs PHP built-in web server';

    public function __construct(
        private readonly string $address = '0.0.0.0',
        private readonly int $port = 8890,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setHelp(
                'In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in a virtual machine.'
            )
            ->addOption('address', 'a', InputOption::VALUE_OPTIONAL, 'Host to serve at', $this->address)
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', $this->port)
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
        $socket->bind();

        $io->success(
            sprintf(
                'Listening on "%s".',
                $socket->getUri(),
            )
        );

        if (\function_exists('pcntl_signal')) {
            $io->success('Quit the server with CTRL-C or COMMAND-C.');

            \pcntl_signal(\SIGINT, static function () use ($socket): void {
                $socket->close();
                exit(1);
            });
        }

        foreach ($socket->read() as $message) {
            if ($message[0] === Connection::TYPE_ERROR) {
                $io->error('Connection closed with error: ' . $message[1]);
                break;
            }

            $data = \json_decode($message[1], null, 512, JSON_THROW_ON_ERROR);
            $type = match ($data[0]) {
                Connection::MESSAGE_TYPE_VAR_DUMPER => 'VarDumper',
                Connection::MESSAGE_TYPE_LOGGER => 'Logger',
                default => 'Plain text',
            };

            $io->block($data[1], $type);
        }

        return ExitCode::OK;
    }
}
