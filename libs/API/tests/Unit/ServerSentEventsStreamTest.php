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
        }, pollIntervalMicros: 0);

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
        $this->assertSame(0, $stream->getSize());
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

    public function testReadReturnsEmptyAfterClose(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'data';
            return true;
        }, pollIntervalMicros: 0);

        $stream->close();
        $this->assertSame('', $stream->read(1024));
    }

    public function testReadReturnsEmptyOnSubsequentCallsAfterEof(): void
    {
        $stream = new ServerSentEventsStream(static fn(array &$buffer) => false);

        $stream->read(1024);
        $this->assertTrue($stream->eof());

        $this->assertSame('', $stream->read(1024));
        $this->assertSame('', $stream->read(1024));
    }

    public function testCloseStopsInterruptibleSleep(): void
    {
        $stream = new ServerSentEventsStream(
            static function (array &$buffer) {
                return true;
            },
            pollIntervalMicros: 1_000_000,
            sleepChunkMicros: 10_000,
        );

        $stream->close();
        $output = $stream->read(1024);

        $this->assertSame('', $output);
        $this->assertTrue($stream->eof());
    }

    public function testDetachStopsSubsequentReads(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'data';
            return true;
        }, pollIntervalMicros: 0);

        $result = $stream->detach();

        $this->assertNull($result);
        $this->assertTrue($stream->eof());
        $this->assertSame('', $stream->read(1024));
    }

    public function testCallbackNotCalledAfterEof(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            return false;
        });

        $stream->read(1024);
        $this->assertSame(1, $callCount);

        $stream->read(1024);
        $stream->read(1024);
        $this->assertSame(1, $callCount);
    }

    public function testZeroPollIntervalSkipsSleep(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            return $callCount < 100;
        }, pollIntervalMicros: 0);

        $start = hrtime(true);
        for ($i = 0; $i < 50; $i++) {
            $stream->read(1024);
        }
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $this->assertLessThan(50, $elapsedMs);
        $this->assertSame(50, $callCount);
    }

    public function testCustomPollInterval(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            $buffer[] = "call-$callCount";
            return $callCount < 3;
        }, pollIntervalMicros: 0);

        $stream->read(1024);
        $stream->read(1024);
        $output = $stream->read(1024);

        $this->assertStringContainsString('data: call-3', $output);
        $this->assertTrue($stream->eof());
    }

    public function testOutputFormatSseCompliant(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = json_encode(['type' => 'test', 'payload' => []]);
            return false;
        });

        $output = $stream->read(1024);

        $this->assertStringStartsWith('data: ', $output);
        $this->assertStringEndsWith("\n\n", $output);

        $lines = explode("\n", trim($output));
        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('data: ', $lines[0]);

        $jsonData = json_decode(substr($lines[0], 6), true);
        $this->assertSame('test', $jsonData['type']);
    }

    public function testEmptyBufferProducesOnlyNewline(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            return false;
        });

        $output = $stream->read(1024);
        $this->assertSame("\n", $output);
    }

    public function testMultipleBufferItemsFormattedAsSeparateDataLines(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'event-a';
            $buffer[] = 'event-b';
            $buffer[] = 'event-c';
            return false;
        });

        $output = $stream->read(1024);
        $lines = explode("\n", $output);

        $this->assertSame('data: event-a', $lines[0]);
        $this->assertSame('data: event-b', $lines[1]);
        $this->assertSame('data: event-c', $lines[2]);
    }

    public function testStreamContinuesAcrossMultipleReads(): void
    {
        $callCount = 0;
        $stream = new ServerSentEventsStream(static function (array &$buffer) use (&$callCount) {
            $callCount++;
            $buffer[] = "event-$callCount";
            return $callCount < 5;
        }, pollIntervalMicros: 0);

        $outputs = [];
        while (!$stream->eof()) {
            $outputs[] = $stream->read(1024);
        }

        $this->assertCount(5, $outputs);
        $this->assertStringContainsString('data: event-1', $outputs[0]);
        $this->assertStringContainsString('data: event-5', $outputs[4]);
        $this->assertTrue($stream->eof());
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
        }, pollIntervalMicros: 0);

        $output1 = $stream->read(1024);
        $this->assertStringContainsString('data: first-call', $output1);

        $output2 = $stream->read(1024);
        $this->assertStringNotContainsString('first-call', $output2);
    }

    public function testLastCallDataIsReturnedWhenClosureReturnsFalse(): void
    {
        $stream = new ServerSentEventsStream(static function (array &$buffer) {
            $buffer[] = 'final-event';
            return false;
        });

        $output = $stream->read(1024);

        $this->assertStringContainsString('data: final-event', $output);
        $this->assertTrue($stream->eof());
    }

    public function testInterruptibleSleepRespectsChunkSize(): void
    {
        $stream = new ServerSentEventsStream(
            static function (array &$buffer) {
                return true;
            },
            pollIntervalMicros: 100_000,
            sleepChunkMicros: 20_000,
        );

        $start = hrtime(true);
        $stream->read(1024);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $this->assertGreaterThan(80, $elapsedMs);
        $this->assertLessThan(250, $elapsedMs);
    }
}
