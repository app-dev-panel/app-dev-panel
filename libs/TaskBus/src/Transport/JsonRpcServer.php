<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class JsonRpcServer
{
    /** @var resource|null */
    private $serverSocket = null;
    private bool $running = false;

    public function __construct(
        private readonly JsonRpcHandler $handler,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Start listening on a TCP or Unix socket.
     *
     * @param non-empty-string $address TCP: "tcp://127.0.0.1:9800" or Unix: "unix:///tmp/task-bus.sock"
     */
    public function listen(string $address): void
    {
        $this->serverSocket = stream_socket_server($address, $errno, $errstr);
        if ($this->serverSocket === false) {
            throw new \RuntimeException("Failed to bind to {$address}: [{$errno}] {$errstr}");
        }

        $this->logger->info("TaskBus JSON-RPC server listening on {$address}");
        $this->running = true;

        while ($this->running) {
            $client = @stream_socket_accept($this->serverSocket, 1);
            if ($client === false) {
                continue;
            }

            try {
                $this->handleClient($client);
            } catch (\Throwable $e) {
                $this->logger->error("Client error: {$e->getMessage()}");
            } finally {
                fclose($client);
            }
        }
    }

    public function stop(): void
    {
        $this->running = false;
        if ($this->serverSocket !== null) {
            fclose($this->serverSocket);
            $this->serverSocket = null;
        }
    }

    /**
     * Handle a single JSON-RPC request string and return the response.
     * Useful for testing or embedding without a socket.
     */
    public function handleRequest(string $json): ?string
    {
        return $this->handler->handle($json);
    }

    /**
     * @param resource $client
     */
    private function handleClient($client): void
    {
        stream_set_timeout($client, 5);
        $data = '';

        while (!feof($client)) {
            $chunk = fread($client, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;

            if (str_contains($data, "\n")) {
                break;
            }
        }

        $data = trim($data);
        if ($data === '') {
            return;
        }

        $this->logger->debug("Received: {$data}");
        $response = $this->handler->handle($data);

        if ($response !== null) {
            fwrite($client, $response . "\n");
            $this->logger->debug("Sent: {$response}");
        }
    }
}
