<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ServerSentEventsStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStreamTest extends TestCase
{
    public function testImplementsStreamInterface(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testReadCallsStreamClosure(): void
    {
        $called = false;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$called) {
            $called = true;
            $buffer[] = json_encode(['type' => 'test']);
            return false;
        });

        $output = $stream->read(1024);
        $this->assertTrue($called);
        $this->assertStringContainsString('data: {"type":"test"}', $output);
    }

    public function testEofAfterStreamReturnsFalse(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertFalse($stream->eof());

        $stream->read(1024);
        $this->assertTrue($stream->eof());
    }

    public function testEofFalseWhileStreamReturnsTrue(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            return $callCount < 3;
        });

        $stream->read(1024);
        $this->assertFalse($stream->eof());
    }

    public function testClose(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => true);
        $this->assertFalse($stream->eof());

        $stream->close();
        $this->assertTrue($stream->eof());
    }

    public function testDetach(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => true);
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testGetSize(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertNull($stream->getSize());
    }

    public function testTell(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertSame(0, $stream->tell());
    }

    public function testIsNotSeekable(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeekThrowsException(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->expectException(\RuntimeException::class);
        $stream->seek(0);
    }

    public function testRewindThrowsException(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->expectException(\RuntimeException::class);
        $stream->rewind();
    }

    public function testIsNotWritable(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertFalse($stream->isWritable());
    }

    public function testWriteThrowsException(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->expectException(\RuntimeException::class);
        $stream->write('test');
    }

    public function testIsReadable(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertTrue($stream->isReadable());
    }

    public function testGetMetadata(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);
        $this->assertSame([], $stream->getMetadata());
    }

    public function testGetContents(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'event-data';
            return false;
        });

        $contents = $stream->getContents();
        $this->assertStringContainsString('data: event-data', $contents);
    }

    public function testToString(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'hello';
            return false;
        });

        $this->assertStringContainsString('data: hello', (string) $stream);
    }

    public function testMultipleBufferItems(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'first';
            $buffer[] = 'second';
            return false;
        });

        $output = $stream->read(1024);
        $this->assertStringContainsString("data: first\n", $output);
        $this->assertStringContainsString("data: second\n", $output);
    }

    public function testBufferIsClearedAfterRead(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                $buffer[] = 'first-call';
            }
            return $callCount < 2;
        });

        $output1 = $stream->read(1024);
        $this->assertStringContainsString('data: first-call', $output1);

        $output2 = $stream->read(1024);
        $this->assertStringNotContainsString('first-call', $output2);
    }

    public function testFromGeneratorReadsYieldedValues(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'first';
            yield 'second';
        });

        $output1 = $stream->read(1024);
        $this->assertSame("data: first\n\n", $output1);

        $output2 = $stream->read(1024);
        $this->assertSame("data: second\n\n", $output2);
    }

    public function testFromGeneratorEofAfterGeneratorExhausted(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'only';
        });

        $this->assertFalse($stream->eof());

        $stream->read(1024);
        $this->assertFalse($stream->eof());

        $stream->read(1024);
        $this->assertTrue($stream->eof());
    }

    public function testFromGeneratorEmptyStringSignalsEof(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'data';
            yield '';
        });

        $stream->read(1024);
        $this->assertFalse($stream->eof());

        $stream->read(1024);
        $this->assertTrue($stream->eof());
    }

    public function testFromGeneratorNullSignalsEof(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'data';
            yield null;
        });

        $stream->read(1024);
        $this->assertFalse($stream->eof());

        $stream->read(1024);
        $this->assertTrue($stream->eof());
    }

    public function testFromGeneratorFormatsAsSseData(): void
    {
        $json = json_encode(['type' => 'debug-updated', 'payload' => []]);
        $stream = ServerSentEventsStream::fromGenerator(static function () use ($json) {
            yield $json;
        });

        $output = $stream->read(1024);

        $this->assertSame("data: {$json}\n\n", $output);
    }

    public function testFromGeneratorGetContents(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'event-data';
        });

        $contents = $stream->getContents();

        $this->assertSame("data: event-data\n\n", $contents);
    }

    public function testFromGeneratorToString(): void
    {
        $stream = ServerSentEventsStream::fromGenerator(static function () {
            yield 'hello-generator';
        });

        $this->assertSame("data: hello-generator\n\n", (string) $stream);
    }
}
