<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * TCP server that accepts Monolog JSON log messages.
 *
 * Compatible with Monolog\Handler\SocketHandler — each message is a newline-delimited JSON record.
 * Messages are stored via the StorageInterface as log collector entries.
 */
#[AsCommand(name: 'monolog:serve', description: 'Start a TCP server for Monolog log messages')]
final class MonologTcpServerCommand extends Command
{
    public const COMMAND_NAME = 'monolog:serve';
    public const DEFAULT_HOST = '0.0.0.0';
    public const DEFAULT_PORT = 9913;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly StorageInterface $storage,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setHelp('Starts a TCP server that accepts Monolog JSON log messages (newline-delimited).')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to bind to', self::DEFAULT_HOST)
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port to listen on', self::DEFAULT_PORT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('ADP Monolog TCP Server');

        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');

        $server = @stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
        if ($server === false) {
            $this->logger->error('Failed to start Monolog TCP server.', ['error' => $errstr, 'errno' => $errno]);
            $io->error(sprintf('Failed to start server: %s (%d)', $errstr, $errno));
            return Command::FAILURE;
        }

        stream_set_blocking($server, false);

        $this->logger->info('Monolog TCP server started.', ['host' => $host, 'port' => $port]);
        $io->success(sprintf('Listening on tcp://%s:%d', $host, $port));

        if (\function_exists('pcntl_signal')) {
            $io->success('Quit the server with CTRL-C or COMMAND-C.');

            \pcntl_signal(\SIGINT, static function () use ($server): void {
                fclose($server);
                exit(0);
            });
        }

        /** @var resource[] $clients */
        $clients = [];
        /** @var array<int, string> $buffers */
        $buffers = [];

        while (true) {
            $read = array_merge([$server], $clients);
            $write = null;
            $except = null;

            if (@stream_select($read, $write, $except, 1) === false) {
                continue;
            }

            // Accept new connections
            if (in_array($server, $read, true)) {
                $client = @stream_socket_accept($server, 0);
                if ($client !== false) {
                    $clientId = (int) $client;
                    $clients[$clientId] = $client;
                    $buffers[$clientId] = '';
                    $this->logger->debug('Client connected.', ['id' => $clientId]);
                }
            }

            // Read from existing clients
            foreach ($clients as $clientId => $client) {
                if (!in_array($client, $read, true)) {
                    continue;
                }

                $data = @fread($client, 65536);
                if ($data === false || $data === '') {
                    // Client disconnected
                    fclose($client);
                    unset($clients[$clientId], $buffers[$clientId]);
                    $this->logger->debug('Client disconnected.', ['id' => $clientId]);
                    continue;
                }

                $buffers[$clientId] .= $data;

                // Process complete lines (newline-delimited)
                while (($pos = strpos($buffers[$clientId], "\n")) !== false) {
                    $line = substr($buffers[$clientId], 0, $pos);
                    $buffers[$clientId] = substr($buffers[$clientId], $pos + 1);

                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }

                    $this->processMessage($line, $io);
                }
            }
        }
    }

    private function processMessage(string $json, SymfonyStyle $io): void
    {
        try {
            /** @var array{message?: string, context?: array, level?: int, level_name?: string, channel?: string, datetime?: string, extra?: array} $record */
            $record = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Failed to decode Monolog message.', ['error' => $e->getMessage()]);
            $io->warning('Failed to decode message: ' . $e->getMessage());
            return;
        }

        $message = $record['message'] ?? '';
        $level = strtolower($record['level_name'] ?? 'debug');
        $context = $record['context'] ?? [];
        $channel = $record['channel'] ?? 'app';
        $datetime = $record['datetime'] ?? date('c');
        $extra = $record['extra'] ?? [];

        $logEntry = [
            'time' => $this->parseDateTime($datetime),
            'level' => $level,
            'message' => $message,
            'context' => array_merge($context, $extra !== [] ? ['_extra' => $extra] : []),
            'line' => $channel,
        ];

        $service = $context['project'] ?? $channel;

        $idGenerator = new DebuggerIdGenerator();
        $id = $idGenerator->getId();

        $collectors = ['logs' => [$logEntry]];
        $summary = [
            'id' => $id,
            'collectors' => [['id' => 'logs', 'name' => 'logs']],
            'context' => [
                'type' => 'generic',
                'service' => $service,
            ],
            'logger' => ['total' => 1],
        ];

        $this->storage->write($id, $summary, $collectors, $collectors);

        $this->logger->debug('Log message stored.', ['id' => $id, 'level' => $level]);
        $io->writeln(sprintf(
            '<fg=%s>[%s]</> %s: %s',
            $this->levelColor($level),
            strtoupper($level),
            $channel,
            $message,
        ));
    }

    private function parseDateTime(string $datetime): float
    {
        $timestamp = strtotime($datetime);
        return $timestamp !== false ? (float) $timestamp : microtime(true);
    }

    private function levelColor(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical', 'error' => 'red',
            'warning' => 'yellow',
            'notice', 'info' => 'green',
            default => 'gray',
        };
    }
}
