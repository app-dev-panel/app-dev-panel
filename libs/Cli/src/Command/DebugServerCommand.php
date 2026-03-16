<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'dev', description: 'Start ADP debug socket server')]
final class DebugServerCommand extends Command
{
    public const COMMAND_NAME = 'dev';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly string $address = '0.0.0.0',
        private readonly int $port = 8890,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setHelp(
                'In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in a virtual machine.',
            )
            ->addOption('address', 'a', InputOption::VALUE_OPTIONAL, 'Host to serve at', $this->address)
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', $this->port)
            ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'It is only used for testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ADP Debug Server');

        $env = $input->getOption('env');
        if ($env === 'test') {
            return Command::SUCCESS;
        }

        try {
            $connection = Connection::create();
            $connection->bind();
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to start debug server.', ['error' => $e->getMessage()]);
            $io->error('Failed to start debug server: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->logger->info('Debug server started.', ['address' => $connection->getUri()]);
        $io->success(sprintf('Listening on "%s".', $connection->getUri()));

        if (\function_exists('pcntl_signal')) {
            $io->success('Quit the server with CTRL-C or COMMAND-C.');

            \pcntl_signal(\SIGINT, function () use ($connection): void {
                $this->logger->info('Debug server shutting down.');
                $connection->close();
                exit(1);
            });
        }

        $reader = new SocketReader($connection->getSocket());

        foreach ($reader->read() as $message) {
            if ($message[0] === Connection::TYPE_ERROR) {
                $this->logger->error('Connection closed with error.', ['error' => $message[1]]);
                $io->error('Connection closed with error: ' . $message[1]);
                break;
            }

            try {
                $data = \json_decode($message[1], null, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->logger->warning('Failed to decode message.', ['error' => $e->getMessage()]);
                $io->warning('Failed to decode message: ' . $e->getMessage());
                continue;
            }
            $type = match ($data[0]) {
                Connection::MESSAGE_TYPE_VAR_DUMPER => 'VarDumper',
                Connection::MESSAGE_TYPE_LOGGER => 'Logger',
                default => 'Plain text',
            };

            $this->logger->debug('Message received.', ['type' => $type]);
            $io->block($data[1], $type);
        }

        return Command::SUCCESS;
    }
}
