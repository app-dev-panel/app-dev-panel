<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Mail;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Non-blocking single-threaded SMTP listener. Built on stream_socket_server + stream_select,
 * no extensions required. Intended for dev mail capture only — never run in production.
 *
 * Usage:
 *   $server = new SmtpServer('127.0.0.1', 1025, $ingestion);
 *   $server->start();
 *   while (!$server->shouldStop()) { $server->tick(1.0); }
 *   $server->stop();
 */
final class SmtpServer
{
    private const int READ_CHUNK_SIZE = 65536;
    private const float SESSION_IDLE_TIMEOUT = 300.0;

    /** @var resource|null */
    private $listenSocket = null;

    /** @var array<int, array{socket: resource, session: SmtpSession, peer: string, lastActivity: float}> */
    private array $connections = [];

    private bool $stopRequested = false;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly StandaloneMailerIngestion $ingestion,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly string $hostname = 'adp-smtp',
        private readonly int $maxMessageSize = 20 * 1024 * 1024,
    ) {}

    public function start(): void
    {
        $address = $this->formatAddress($this->host, $this->port);
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if ($socket === false) {
            throw new \RuntimeException(sprintf(
                'Failed to bind SMTP listener on %s: %s (%d)',
                $address,
                $errstr,
                $errno,
            ));
        }
        stream_set_blocking($socket, false);
        $this->listenSocket = $socket;
        $this->logger->info(sprintf('ADP SMTP listener started on %s', $address));
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    public function shouldStop(): bool
    {
        return $this->stopRequested;
    }

    public function stop(): void
    {
        foreach ($this->connections as $id => $conn) {
            @fclose($conn['socket']);
            unset($this->connections[$id]);
        }
        if ($this->listenSocket !== null) {
            @fclose($this->listenSocket);
            $this->listenSocket = null;
        }
        $this->logger->info('ADP SMTP listener stopped');
    }

    public function port(): int
    {
        if ($this->listenSocket === null) {
            return $this->port;
        }
        $name = stream_socket_get_name($this->listenSocket, false);
        if ($name === false) {
            return $this->port;
        }
        $pos = strrpos($name, ':');
        return $pos === false ? $this->port : (int) substr($name, $pos + 1);
    }

    /**
     * Run a single iteration of the event loop with the given timeout (seconds).
     * Accepts new connections and services ready ones.
     */
    public function tick(float $timeout = 1.0): void
    {
        if ($this->listenSocket === null) {
            throw new \LogicException('SmtpServer::start() must be called before tick().');
        }
        $read = [$this->listenSocket];
        foreach ($this->connections as $id => $conn) {
            $read[$id] = $conn['socket'];
        }
        $write = null;
        $except = null;
        $seconds = (int) $timeout;
        $micros = (int) (($timeout - $seconds) * 1_000_000);
        $ready = @stream_select($read, $write, $except, $seconds, $micros);
        if ($ready === false || $ready === 0) {
            $this->reapIdleConnections();
            return;
        }
        foreach ($read as $key => $sock) {
            if ($sock === $this->listenSocket) {
                $this->acceptConnection();
                continue;
            }
            $this->serviceConnection((int) $key);
        }
        $this->reapIdleConnections();
    }

    private function acceptConnection(): void
    {
        if ($this->listenSocket === null) {
            return;
        }
        $peer = '';
        $client = @stream_socket_accept($this->listenSocket, 0, $peer);
        if ($client === false) {
            return;
        }
        stream_set_blocking($client, false);
        $id = (int) $client;
        $session = new SmtpSession($this->hostname, $this->maxMessageSize);
        $greeting = $session->greeting();
        @fwrite($client, $greeting);
        $this->connections[$id] = [
            'socket' => $client,
            'session' => $session,
            'peer' => $peer,
            'lastActivity' => microtime(true),
        ];
        $this->logger->debug(sprintf('SMTP connection accepted from %s', $peer));
    }

    private function serviceConnection(int $id): void
    {
        if (!isset($this->connections[$id])) {
            return;
        }
        $conn = &$this->connections[$id];
        $chunk = @fread($conn['socket'], self::READ_CHUNK_SIZE);
        if ($chunk === false || $chunk === '') {
            if (feof($conn['socket'])) {
                $this->closeConnection($id);
            }
            return;
        }
        $conn['lastActivity'] = microtime(true);
        $response = $conn['session']->feed($chunk);
        if ($response !== '') {
            @fwrite($conn['socket'], $response);
        }

        while ($conn['session']->hasCompletedMessage()) {
            $envelope = $conn['session']->takeCompletedMessage();
            if ($envelope === null) {
                break;
            }
            try {
                $this->ingestion->ingest($envelope, [
                    'peer' => $conn['peer'],
                    'acceptedAt' => date(DATE_ATOM),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to ingest SMTP message: ' . $e->getMessage());
            }
        }

        if ($conn['session']->isClosed()) {
            $this->closeConnection($id);
        }
    }

    private function closeConnection(int $id): void
    {
        if (!isset($this->connections[$id])) {
            return;
        }
        @fclose($this->connections[$id]['socket']);
        unset($this->connections[$id]);
    }

    private function reapIdleConnections(): void
    {
        $now = microtime(true);
        foreach ($this->connections as $id => $conn) {
            if (($now - $conn['lastActivity']) > self::SESSION_IDLE_TIMEOUT) {
                @fwrite($conn['socket'], "421 Idle timeout; closing connection\r\n");
                $this->closeConnection($id);
            }
        }
    }

    private function formatAddress(string $host, int $port): string
    {
        if (str_contains($host, ':') && !str_starts_with($host, '[')) {
            $host = '[' . $host . ']';
        }
        return 'tcp://' . $host . ':' . $port;
    }
}
