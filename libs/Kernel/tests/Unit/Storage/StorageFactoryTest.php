<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageFactory;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;

final class StorageFactoryTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/adp-factory-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->path)) {
            FileHelper::removeDirectory($this->path);
        }
    }

    #[RequiresPhpExtension('pdo_sqlite')]
    public function testCreateSqliteDriver(): void
    {
        $storage = StorageFactory::create('sqlite', $this->path, new DebuggerIdGenerator());

        $this->assertInstanceOf(SqliteStorage::class, $storage);
    }

    public function testCreateFileDriver(): void
    {
        $storage = StorageFactory::create('file', $this->path, new DebuggerIdGenerator());

        $this->assertInstanceOf(FileStorage::class, $storage);
    }

    public function testCreateWithCustomClass(): void
    {
        $storage = StorageFactory::create(FileStorage::class, $this->path, new DebuggerIdGenerator());

        $this->assertInstanceOf(FileStorage::class, $storage);
    }

    #[RequiresPhpExtension('pdo_sqlite')]
    public function testCreateWithCustomSqliteClass(): void
    {
        $storage = StorageFactory::create(SqliteStorage::class, $this->path, new DebuggerIdGenerator());

        $this->assertInstanceOf(SqliteStorage::class, $storage);
    }

    public function testCreateWithNonExistentClassThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not a valid built-in driver or class name');

        StorageFactory::create('NonExistent\\Storage\\Class', $this->path, new DebuggerIdGenerator());
    }

    public function testCreateWithNonStorageClassThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        StorageFactory::create(\stdClass::class, $this->path, new DebuggerIdGenerator());
    }

    #[RequiresPhpExtension('pdo_sqlite')]
    public function testCreatedStorageIsFullyFunctional(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = StorageFactory::create('sqlite', $this->path, $idGenerator);

        $storage->write('test-id', ['id' => 'test-id'], ['key' => 'value'], []);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'test-id');
        $this->assertArrayHasKey('test-id', $result);
    }

    public function testFileDriverIsFullyFunctional(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = StorageFactory::create('file', $this->path, $idGenerator);

        $storage->write('test-id', ['id' => 'test-id'], ['key' => 'value'], []);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'test-id');
        $this->assertArrayHasKey('test-id', $result);
    }

    public function testExcludedClassesPassedThrough(): void
    {
        $storage = StorageFactory::create('file', $this->path, new DebuggerIdGenerator(), ['SomeClass']);

        $this->assertInstanceOf(FileStorage::class, $storage);
    }
}
