<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\IgnoreConfig;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class IgnoreConfigTest extends TestCase
{
    public function testRequestNotIgnoredByDefault(): void
    {
        $config = new IgnoreConfig();
        $request = $this->createRequest('/api/data');

        $this->assertFalse($config->isRequestIgnored($request));
    }

    public function testRequestIgnoredByHeader(): void
    {
        $config = new IgnoreConfig();
        $request = $this->createRequest('/api/data', hasIgnoreHeader: true);

        $this->assertTrue($config->isRequestIgnored($request));
    }

    public function testRequestIgnoredByPattern(): void
    {
        $config = new IgnoreConfig(ignoredRequests: ['/debug/**', '/health']);

        $this->assertTrue($config->isRequestIgnored($this->createRequest('/debug/api/list')));
        $this->assertTrue($config->isRequestIgnored($this->createRequest('/health')));
        $this->assertFalse($config->isRequestIgnored($this->createRequest('/api/users')));
    }

    public function testCommandIgnoredWhenNull(): void
    {
        $config = new IgnoreConfig();
        $this->assertTrue($config->isCommandIgnored(null));
    }

    public function testCommandIgnoredWhenEmpty(): void
    {
        $config = new IgnoreConfig();
        $this->assertTrue($config->isCommandIgnored(''));
    }

    public function testCommandNotIgnoredByDefault(): void
    {
        $config = new IgnoreConfig();
        $this->assertFalse($config->isCommandIgnored('app:migrate'));
    }

    public function testCommandIgnoredByPattern(): void
    {
        $config = new IgnoreConfig(ignoredCommands: ['debug:*', 'cache:clear']);

        $this->assertTrue($config->isCommandIgnored('debug:query'));
        $this->assertTrue($config->isCommandIgnored('cache:clear'));
        $this->assertFalse($config->isCommandIgnored('app:migrate'));
    }

    public function testWithIgnoredRequests(): void
    {
        $config = new IgnoreConfig();
        $newConfig = $config->withIgnoredRequests(['/debug/*']);

        $this->assertFalse($config->isRequestIgnored($this->createRequest('/debug/api')));
        $this->assertTrue($newConfig->isRequestIgnored($this->createRequest('/debug/api')));
    }

    public function testWithIgnoredCommands(): void
    {
        $config = new IgnoreConfig();
        $newConfig = $config->withIgnoredCommands(['debug:*']);

        $this->assertFalse($config->isCommandIgnored('debug:query'));
        $this->assertTrue($newConfig->isCommandIgnored('debug:query'));
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
