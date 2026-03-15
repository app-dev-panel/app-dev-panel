<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Service;

use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceDescriptor;
use PHPUnit\Framework\TestCase;

final class FileServiceRegistryTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp-registry-test-' . uniqid();
        mkdir($this->storagePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storagePath);
    }

    private function createRegistry(): FileServiceRegistry
    {
        return new FileServiceRegistry($this->storagePath);
    }

    private function descriptor(string $service = 'test-svc', string $language = 'python'): ServiceDescriptor
    {
        $now = microtime(true);

        return new ServiceDescriptor($service, $language, 'http://localhost:9090', ['config', 'routes'], $now, $now);
    }

    public function testRegisterAndResolve(): void
    {
        $registry = $this->createRegistry();
        $descriptor = $this->descriptor();

        $registry->register($descriptor);
        $resolved = $registry->resolve('test-svc');

        $this->assertNotNull($resolved);
        $this->assertSame('test-svc', $resolved->service);
        $this->assertSame('python', $resolved->language);
        $this->assertSame('http://localhost:9090', $resolved->inspectorUrl);
        $this->assertSame(['config', 'routes'], $resolved->capabilities);
    }

    public function testResolveUnknown(): void
    {
        $registry = $this->createRegistry();

        $this->assertNull($registry->resolve('nonexistent'));
    }

    public function testDeregister(): void
    {
        $registry = $this->createRegistry();
        $registry->register($this->descriptor());

        $this->assertNotNull($registry->resolve('test-svc'));

        $registry->deregister('test-svc');

        $this->assertNull($registry->resolve('test-svc'));
    }

    public function testDeregisterNonexistent(): void
    {
        $registry = $this->createRegistry();

        // Should not throw
        $registry->deregister('nonexistent');
        $this->assertSame([], $registry->all());
    }

    public function testHeartbeat(): void
    {
        $registry = $this->createRegistry();
        $descriptor = $this->descriptor();
        $registry->register($descriptor);

        $before = $registry->resolve('test-svc');
        $this->assertNotNull($before);

        usleep(10000); // 10ms
        $registry->heartbeat('test-svc');

        $after = $registry->resolve('test-svc');
        $this->assertNotNull($after);
        $this->assertGreaterThan($before->lastSeenAt, $after->lastSeenAt);
    }

    public function testHeartbeatUnknownService(): void
    {
        $registry = $this->createRegistry();

        // Should not throw
        $registry->heartbeat('nonexistent');
        $this->assertSame([], $registry->all());
    }

    public function testAll(): void
    {
        $registry = $this->createRegistry();

        $this->assertSame([], $registry->all());

        $registry->register($this->descriptor('svc-a', 'python'));
        $registry->register($this->descriptor('svc-b', 'typescript'));

        $all = $registry->all();
        $this->assertCount(2, $all);

        $names = array_map(static fn(ServiceDescriptor $d) => $d->service, $all);
        $this->assertContains('svc-a', $names);
        $this->assertContains('svc-b', $names);
    }

    public function testRegisterOverwritesExisting(): void
    {
        $registry = $this->createRegistry();
        $now = microtime(true);

        $registry->register(new ServiceDescriptor('svc', 'python', 'http://old:9090', ['config'], $now, $now));
        $registry->register(
            new ServiceDescriptor('svc', 'python', 'http://new:9090', ['config', 'routes'], $now, $now),
        );

        $resolved = $registry->resolve('svc');
        $this->assertNotNull($resolved);
        $this->assertSame('http://new:9090', $resolved->inspectorUrl);
        $this->assertSame(['config', 'routes'], $resolved->capabilities);
    }

    public function testPersistsBetweenInstances(): void
    {
        $registry1 = $this->createRegistry();
        $registry1->register($this->descriptor('persistent-svc'));

        $registry2 = $this->createRegistry();
        $resolved = $registry2->resolve('persistent-svc');

        $this->assertNotNull($resolved);
        $this->assertSame('persistent-svc', $resolved->service);
    }

    public function testEmptyStoragePath(): void
    {
        $newPath = $this->storagePath . '/nested/deep';
        $registry = new FileServiceRegistry($newPath);
        $registry->register($this->descriptor());

        $this->assertNotNull($registry->resolve('test-svc'));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
