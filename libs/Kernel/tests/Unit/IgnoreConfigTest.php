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

    public function testCommandIgnoredByEnvironmentVariable(): void
    {
        $previousValue = getenv('YII_DEBUG_IGNORE');

        putenv('YII_DEBUG_IGNORE=true');
        $config = new IgnoreConfig();
        $this->assertTrue($config->isCommandIgnored('any:command'));

        // Restore
        if ($previousValue === false) {
            putenv('YII_DEBUG_IGNORE');
        } else {
            putenv('YII_DEBUG_IGNORE=' . $previousValue);
        }
    }

    public function testCommandNotIgnoredWhenEnvVarNotSet(): void
    {
        $previousValue = getenv('YII_DEBUG_IGNORE');

        putenv('YII_DEBUG_IGNORE');
        $config = new IgnoreConfig();
        $this->assertFalse($config->isCommandIgnored('app:command'));

        // Restore
        if ($previousValue !== false) {
            putenv('YII_DEBUG_IGNORE=' . $previousValue);
        }
    }

    public function testCommandNotIgnoredWhenEnvVarNotTrue(): void
    {
        $previousValue = getenv('YII_DEBUG_IGNORE');

        putenv('YII_DEBUG_IGNORE=false');
        $config = new IgnoreConfig();
        $this->assertFalse($config->isCommandIgnored('app:command'));

        // Restore
        if ($previousValue === false) {
            putenv('YII_DEBUG_IGNORE');
        } else {
            putenv('YII_DEBUG_IGNORE=' . $previousValue);
        }
    }

    public function testWithIgnoredRequestsReturnsNewInstance(): void
    {
        $config = new IgnoreConfig();
        $newConfig = $config->withIgnoredRequests(['/debug/*']);

        $this->assertNotSame($config, $newConfig);
    }

    public function testWithIgnoredCommandsReturnsNewInstance(): void
    {
        $config = new IgnoreConfig();
        $newConfig = $config->withIgnoredCommands(['debug:*']);

        $this->assertNotSame($config, $newConfig);
    }

    public function testWithIgnoredRequestsPreservesCommands(): void
    {
        $config = new IgnoreConfig(ignoredCommands: ['cache:*']);
        $newConfig = $config->withIgnoredRequests(['/debug/*']);

        // New config should still have the commands from original
        $this->assertTrue($newConfig->isCommandIgnored('cache:clear'));
        $this->assertTrue($newConfig->isRequestIgnored($this->createRequest('/debug/api')));
    }

    public function testWithIgnoredCommandsPreservesRequests(): void
    {
        $config = new IgnoreConfig(ignoredRequests: ['/debug/*']);
        $newConfig = $config->withIgnoredCommands(['cache:*']);

        // New config should still have the requests from original
        $this->assertTrue($newConfig->isRequestIgnored($this->createRequest('/debug/api')));
        $this->assertTrue($newConfig->isCommandIgnored('cache:clear'));
    }

    public function testRequestIgnoredByHeaderValueNotTrue(): void
    {
        $config = new IgnoreConfig();

        // Header exists but value is not 'true'
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/api/data');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('hasHeader')->with('X-Debug-Ignore')->willReturn(true);
        $request->method('getHeaderLine')->with('X-Debug-Ignore')->willReturn('false');

        $this->assertFalse($config->isRequestIgnored($request));
    }

    public function testMultipleRequestPatterns(): void
    {
        $config = new IgnoreConfig(ignoredRequests: ['/debug/**', '/health', '/metrics/**']);

        $this->assertTrue($config->isRequestIgnored($this->createRequest('/debug/api/list')));
        $this->assertTrue($config->isRequestIgnored($this->createRequest('/health')));
        $this->assertTrue($config->isRequestIgnored($this->createRequest('/metrics/cpu')));
        $this->assertFalse($config->isRequestIgnored($this->createRequest('/api/users')));
    }

    public function testMultipleCommandPatterns(): void
    {
        $config = new IgnoreConfig(ignoredCommands: ['debug:*', 'cache:*', 'migrate']);

        $this->assertTrue($config->isCommandIgnored('debug:query'));
        $this->assertTrue($config->isCommandIgnored('cache:clear'));
        $this->assertTrue($config->isCommandIgnored('migrate'));
        $this->assertFalse($config->isCommandIgnored('app:serve'));
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
