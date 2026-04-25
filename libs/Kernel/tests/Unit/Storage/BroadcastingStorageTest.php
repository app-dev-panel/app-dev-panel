<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use AppDevPanel\Kernel\Storage\BroadcastingStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class BroadcastingStorageTest extends TestCase
{
    public function testWriteDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('write')->with('id1', ['s'], ['d'], ['o']);

        $storage = new BroadcastingStorage($decorated);
        $storage->write('id1', ['s'], ['d'], ['o']);
    }

    public function testWriteBroadcastsEntryCreated(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $decorated = $this->createMock(StorageInterface::class);
            $storage = new BroadcastingStorage($decorated);

            $storage->write('test-entry-id', ['summary'], ['data'], []);

            // Verify the broadcast was received
            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_ENTRY_CREATED, $decoded[0]);
            $this->assertSame('test-entry-id', $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testFlushDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('flush');
        $decorated->method('read')->willReturn([]);

        $storage = new BroadcastingStorage($decorated);
        $storage->flush();
    }

    public function testFlushBroadcastsLatestEntryId(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $decorated = $this->createMock(StorageInterface::class);
            $decorated
                ->method('read')
                ->with(StorageInterface::TYPE_SUMMARY, null)
                ->willReturn(['latest-id' => ['id' => 'latest-id'], 'older-id' => ['id' => 'older-id']]);

            $storage = new BroadcastingStorage($decorated);
            $storage->flush();

            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_ENTRY_CREATED, $decoded[0]);
            $this->assertSame('latest-id', $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testReadDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(StorageInterface::class);
        $decorated
            ->expects($this->once())
            ->method('read')
            ->with(StorageInterface::TYPE_SUMMARY, 'id1')
            ->willReturn(['id1' => ['data']]);

        $storage = new BroadcastingStorage($decorated);
        $this->assertSame(['id1' => ['data']], $storage->read(StorageInterface::TYPE_SUMMARY, 'id1'));
    }

    public function testClearDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('clear');

        $storage = new BroadcastingStorage($decorated);
        $storage->clear();
    }

    public function testAddCollectorDelegatesToDecorated(): void
    {
        $collector = $this->createMock(\AppDevPanel\Kernel\Collector\CollectorInterface::class);
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('addCollector')->with($collector);

        $storage = new BroadcastingStorage($decorated);
        $storage->addCollector($collector);
    }

    public function testGetDataDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('getData')->willReturn(['data']);

        $storage = new BroadcastingStorage($decorated);
        $this->assertSame(['data'], $storage->getData());
    }

    public function testBroadcastFailureDoesNotBreakWrite(): void
    {
        // Broadcaster with no listeners — broadcast is a no-op but write should still succeed
        $decorated = $this->createMock(StorageInterface::class);
        $decorated->expects($this->once())->method('write');

        $storage = new BroadcastingStorage($decorated, new Broadcaster());
        // Should not throw even with no listeners
        $storage->write('id1', [], [], []);
    }

    public function testCustomBroadcasterIsUsed(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $decorated = $this->createMock(StorageInterface::class);

            $storage = new BroadcastingStorage($decorated, $broadcaster);
            $storage->write('custom-id', [], [], []);

            // Verify the custom broadcaster was used by reading the socket
            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_ENTRY_CREATED, $decoded[0]);
            $this->assertSame('custom-id', $decoded[1]);
        } finally {
            $connection->close();
        }
    }
}
