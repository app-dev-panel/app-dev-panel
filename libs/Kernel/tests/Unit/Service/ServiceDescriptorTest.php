<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Service;

use AppDevPanel\Kernel\Service\ServiceDescriptor;
use PHPUnit\Framework\TestCase;

final class ServiceDescriptorTest extends TestCase
{
    public function testSupportsExactCapability(): void
    {
        $descriptor = new ServiceDescriptor('svc', 'python', 'http://localhost:9090', ['config', 'routes'], 1.0, 1.0);

        $this->assertTrue($descriptor->supports('config'));
        $this->assertTrue($descriptor->supports('routes'));
        $this->assertFalse($descriptor->supports('database'));
    }

    public function testSupportsWildcard(): void
    {
        $descriptor = new ServiceDescriptor('svc', 'php', null, ['*'], 1.0, 1.0);

        $this->assertTrue($descriptor->supports('config'));
        $this->assertTrue($descriptor->supports('anything'));
    }

    public function testIsOnline(): void
    {
        $now = microtime(true);
        $online = new ServiceDescriptor('svc', 'python', 'http://localhost:9090', [], $now, $now);
        $offline = new ServiceDescriptor('svc', 'python', 'http://localhost:9090', [], $now, $now - 120);

        $this->assertTrue($online->isOnline());
        $this->assertFalse($offline->isOnline());
    }

    public function testIsOnlineCustomTimeout(): void
    {
        $now = microtime(true);
        $descriptor = new ServiceDescriptor('svc', 'python', 'http://localhost:9090', [], $now, $now - 10);

        $this->assertFalse($descriptor->isOnline(5.0));
        $this->assertTrue($descriptor->isOnline(30.0));
    }

    public function testWithLastSeen(): void
    {
        $descriptor = new ServiceDescriptor('svc', 'python', 'http://localhost:9090', ['config'], 1.0, 1.0);
        $updated = $descriptor->withLastSeen(99.0);

        $this->assertSame(99.0, $updated->lastSeenAt);
        $this->assertSame(1.0, $updated->registeredAt);
        $this->assertSame('svc', $updated->service);
        $this->assertSame('python', $updated->language);
        $this->assertSame(['config'], $updated->capabilities);
    }

    public function testToArrayAndFromArray(): void
    {
        $descriptor = new ServiceDescriptor('auth', 'python', 'http://auth:9090', ['config', 'routes'], 100.0, 200.0);
        $array = $descriptor->toArray();

        $this->assertSame('auth', $array['service']);
        $this->assertSame('python', $array['language']);
        $this->assertSame('http://auth:9090', $array['inspectorUrl']);
        $this->assertSame(['config', 'routes'], $array['capabilities']);
        $this->assertSame(100.0, $array['registeredAt']);
        $this->assertSame(200.0, $array['lastSeenAt']);

        $restored = ServiceDescriptor::fromArray($array);
        $this->assertSame($descriptor->service, $restored->service);
        $this->assertSame($descriptor->language, $restored->language);
        $this->assertSame($descriptor->inspectorUrl, $restored->inspectorUrl);
        $this->assertSame($descriptor->capabilities, $restored->capabilities);
    }

    public function testFromArrayWithDefaults(): void
    {
        $descriptor = ServiceDescriptor::fromArray(['service' => 'minimal']);

        $this->assertSame('minimal', $descriptor->service);
        $this->assertSame('unknown', $descriptor->language);
        $this->assertNull($descriptor->inspectorUrl);
        $this->assertSame([], $descriptor->capabilities);
    }
}
