<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Transport;

/**
 * MCP stdio transport — reads/writes JSON-RPC 2.0 messages over stdin/stdout.
 *
 * Messages are newline-delimited JSON objects.
 */
final class StdioTransport
{
    /** @var resource */
    private mixed $input;

    /** @var resource */
    private mixed $output;

    /**
     * @param resource|null $input
     * @param resource|null $output
     */
    public function __construct(mixed $input = null, mixed $output = null)
    {
        $this->input = $input ?? STDIN;
        $this->output = $output ?? STDOUT;
    }

    /**
     * Read one JSON-RPC message from stdin. Returns null on EOF.
     */
    public function receive(): ?array
    {
        $line = fgets($this->input);

        if ($line === false) {
            return null;
        }

        $line = trim($line);

        if ($line === '') {
            return null;
        }

        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON-RPC message: expected object');
        }

        return $decoded;
    }

    /**
     * Write a JSON-RPC message to stdout.
     */
    public function send(array $message): void
    {
        $json = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        fwrite($this->output, $json . "\n");
        fflush($this->output);
    }
}
