<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Controller;

use AppDevPanel\Api\ServerSentEventsStream;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class DebugServerController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function stream(): ResponseInterface
    {
        if (\function_exists('pcntl_signal')) {
            \pcntl_signal(\SIGINT, static function (): never {
                exit(1);
            });
        }

        $connection = Connection::create();
        $connection->bind();

        $reader = new SocketReader($connection->getSocket());

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(ServerSentEventsStream::fromGenerator(function () use ($reader, $connection) {
                try {
                    foreach ($reader->read() as $message) {
                        match ($message[0]) {
                            Connection::TYPE_ERROR => yield '',
                            Connection::TYPE_RELEASE => connection_aborted() ? yield '' : null,
                            Connection::TYPE_RESULT => yield $message[1],
                            default => null,
                        };
                    }
                } finally {
                    $connection->close();
                }

                return '';
            }));
    }
}
