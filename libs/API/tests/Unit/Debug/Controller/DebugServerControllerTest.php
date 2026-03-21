<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Controller;

use AppDevPanel\Api\Debug\Controller\DebugServerController;
use AppDevPanel\Api\ServerSentEventsStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class DebugServerControllerTest extends TestCase
{
    public function testStreamReturnsSseResponse(): void
    {
        $factory = new Psr17Factory();
        $controller = new DebugServerController($factory);

        $response = $controller->stream();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no-cache', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('keep-alive', $response->getHeaderLine('Connection'));
    }

    public function testStreamBodyIsServerSentEventsStream(): void
    {
        $factory = new Psr17Factory();
        $controller = new DebugServerController($factory);

        $response = $controller->stream();

        $this->assertInstanceOf(ServerSentEventsStream::class, $response->getBody());
    }
}
