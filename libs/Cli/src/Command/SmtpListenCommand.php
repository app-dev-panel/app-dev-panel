<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Mail\SmtpServer;
use AppDevPanel\Kernel\Mail\StandaloneMailerIngestion;
use AppDevPanel\Kernel\Storage\FileStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Starts a standalone SMTP listener that captures outgoing mail into the ADP debug panel.
 *
 * Configure your application's SMTP transport to point at this listener, e.g.
 * Symfony: MAILER_DSN=smtp://127.0.0.1:1025
 * Laravel: MAIL_HOST=127.0.0.1 MAIL_PORT=1025 MAIL_ENCRYPTION=null
 *
 * For PHP `mail()` on Linux, a sendmail-forwarder shim is still required separately.
 */
#[AsCommand(name: 'mail:listen', description: 'Start an SMTP listener that captures mail for the debug panel')]
final class SmtpListenCommand extends Command
{
    /**
     * @param (callable(SmtpServer): bool)|null $stopPredicate Used by tests to break the loop early.
     */
    public function __construct(
        private $stopPredicate = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to bind', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to bind', '1025')
            ->addOption('storage-path', null, InputOption::VALUE_OPTIONAL, 'Debug data storage path')
            ->addOption('hostname', null, InputOption::VALUE_OPTIONAL, 'SMTP banner hostname', 'adp-smtp')
            ->addOption('max-size', null, InputOption::VALUE_OPTIONAL, 'Max message size in bytes', '20971520')
            ->addOption('allow-external', null, InputOption::VALUE_NONE, 'Allow binding on non-loopback host');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');
        $storagePath = (string) ($input->getOption('storage-path') ?? sys_get_temp_dir() . '/adp');
        $hostname = (string) $input->getOption('hostname');
        $maxSize = max(1024, (int) $input->getOption('max-size'));
        $allowExternal = (bool) $input->getOption('allow-external');

        if (!$this->isLoopback($host) && !$allowExternal) {
            $io->error(sprintf(
                'Refusing to bind on non-loopback host "%s" without --allow-external. The SMTP listener captures mail without delivering it.',
                $host,
            ));
            return Command::FAILURE;
        }

        if (!is_dir($storagePath) && !@mkdir($storagePath, 0o777, true) && !is_dir($storagePath)) {
            $io->error(sprintf('Failed to create storage path: %s', $storagePath));
            return Command::FAILURE;
        }

        $storage = new FileStorage($storagePath, new DebuggerIdGenerator());
        $ingestion = new StandaloneMailerIngestion($storage);

        $server = new SmtpServer(
            host: $host,
            port: $port,
            ingestion: $ingestion,
            hostname: $hostname,
            maxMessageSize: $maxSize,
        );

        try {
            $server->start();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->title('ADP SMTP Listener');
        $io->success(sprintf('Listening on %s:%d', $host, $server->port()));
        $io->text([
            sprintf('Storage: %s', $storagePath),
            sprintf('Banner hostname: %s', $hostname),
            sprintf('Max message size: %d bytes', $maxSize),
            '',
            'WARNING: all mail received on this port is captured and NOT delivered.',
            'Press Ctrl+C to stop.',
        ]);

        $this->installSignalHandlers($server);

        $shouldStop = $this->stopPredicate ?? static fn(SmtpServer $s): bool => $s->shouldStop();

        while (!$shouldStop($server)) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            $server->tick(1.0);
        }

        $server->stop();
        $io->text('SMTP listener stopped.');
        return Command::SUCCESS;
    }

    private function isLoopback(string $host): bool
    {
        return $host === '127.0.0.1' || $host === '::1' || $host === 'localhost' || str_starts_with($host, '127.');
    }

    private function installSignalHandlers(SmtpServer $server): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        $handler = static function () use ($server): void {
            $server->requestStop();
        };
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);
    }
}
