<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class DebuggerIgnoreConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new DebuggerIgnoreConfig();

        $this->assertSame([], $config->requests);
        $this->assertSame([], $config->commands);
    }

    public function testCustomValues(): void
    {
        $config = new DebuggerIgnoreConfig(requests: ['/debug/*', '/health'], commands: ['debug:*']);

        $this->assertSame(['/debug/*', '/health'], $config->requests);
        $this->assertSame(['debug:*'], $config->commands);
    }

    public function testRequestNotIgnoredByDefault(): void
    {
        $config = new DebuggerIgnoreConfig();

        $this->assertFalse($config->isRequestIgnored($this->createRequest('/api/data')));
    }

    public function testRequestIgnoredByHeader(): void
    {
        $config = new DebuggerIgnoreConfig();

        $this->assertTrue($config->isRequestIgnored($this->createRequest('/api/data', hasIgnoreHeader: true)));
    }

    public function testRequestIgnoredByPattern(): void
    {
        $config = new DebuggerIgnoreConfig(requests: ['/debug/**', '/health']);

        $this->assertTrue($config->isRequestIgnored($this->createRequest('/debug/api/list')));
        $this->assertTrue($config->isRequestIgnored($this->createRequest('/health')));
        $this->assertFalse($config->isRequestIgnored($this->createRequest('/api/users')));
    }

    public function testCommandIgnoredWhenNull(): void
    {
        $config = new DebuggerIgnoreConfig();
        $this->assertTrue($config->isCommandIgnored(null));
    }

    public function testCommandIgnoredWhenEmpty(): void
    {
        $config = new DebuggerIgnoreConfig();
        $this->assertTrue($config->isCommandIgnored(''));
    }

    public function testCommandNotIgnoredByDefault(): void
    {
        $config = new DebuggerIgnoreConfig();
        $this->assertFalse($config->isCommandIgnored('app:migrate'));
    }

    public function testCommandIgnoredByPattern(): void
    {
        $config = new DebuggerIgnoreConfig(commands: ['debug:*', 'cache:clear']);

        $this->assertTrue($config->isCommandIgnored('debug:query'));
        $this->assertTrue($config->isCommandIgnored('cache:clear'));
        $this->assertFalse($config->isCommandIgnored('app:migrate'));
    }

    public function testCommandIgnoredByEnv(): void
    {
        $config = new DebuggerIgnoreConfig();

        putenv('YII_DEBUG_IGNORE=true');
        $this->assertTrue($config->isCommandIgnored('app:migrate'));
        putenv('YII_DEBUG_IGNORE=false');
    }

    private function createRequest(string $path, bool $hasIgnoreHeader = false): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('hasHeader')->with('X-Debug-Ignore')->willReturn($hasIgnoreHeader);
        $request
            ->method('getHeaderLine')
            ->with('X-Debug-Ignore')
            ->willReturn($hasIgnoreHeader ? 'true' : '');

        return $request;
    }
}
