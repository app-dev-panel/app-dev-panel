<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Psr\Http\Message\ServerRequestInterface;

final class EnvironmentCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|EnvironmentCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock
            ->method('getServerParams')
            ->willReturn([
                'SERVER_NAME' => 'localhost',
                'REQUEST_URI' => '/test',
            ]);

        $collector->collectFromRequest($requestMock);
    }

    protected function getCollector(): CollectorInterface
    {
        return new EnvironmentCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('php', $data);
        $this->assertArrayHasKey('os', $data);
        $this->assertArrayHasKey('git', $data);
        $this->assertArrayHasKey('server', $data);
        $this->assertArrayHasKey('env', $data);

        $git = $data['git'];
        $this->assertArrayHasKey('branch', $git);
        $this->assertArrayHasKey('commit', $git);
        $this->assertArrayHasKey('commitFull', $git);

        $php = $data['php'];
        $this->assertSame(PHP_VERSION, $php['version']);
        $this->assertSame(PHP_SAPI, $php['sapi']);
        $this->assertSame(PHP_BINARY, $php['binary']);
        $this->assertSame(PHP_OS, $php['os']);
        $this->assertIsArray($php['extensions']);
        $this->assertNotEmpty($php['extensions']);
        $this->assertArrayHasKey('xdebug', $php);
        $this->assertArrayHasKey('opcache', $php);
        $this->assertArrayHasKey('pcov', $php);
        $this->assertArrayHasKey('ini', $php);
        $this->assertArrayHasKey('zend_extensions', $php);

        $os = $data['os'];
        $this->assertSame(PHP_OS_FAMILY, $os['family']);
        $this->assertSame(PHP_OS, $os['name']);
        $this->assertNotEmpty($os['uname']);

        $this->assertSame('localhost', $data['server']['SERVER_NAME']);
        $this->assertSame('/test', $data['server']['REQUEST_URI']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('environment', $data);
        $this->assertSame(PHP_VERSION, $data['environment']['php']['version']);
        $this->assertSame(PHP_SAPI, $data['environment']['php']['sapi']);
        $this->assertSame(PHP_OS_FAMILY, $data['environment']['os']);
    }

    public function testCollectFromGlobals(): void
    {
        $collector = new EnvironmentCollector();
        $collector->startup();
        $collector->collectFromGlobals();

        $data = $collector->getCollected();

        $this->assertNotEmpty($data['server']);
        $this->assertSame(PHP_VERSION, $data['php']['version']);

        $collector->shutdown();
    }

    public function testCollectFromRequestWhenInactive(): void
    {
        $collector = new EnvironmentCollector();
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getServerParams')->willReturn(['FOO' => 'bar']);

        $collector->collectFromRequest($requestMock);

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testCollectFromGlobalsWhenInactive(): void
    {
        $collector = new EnvironmentCollector();
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;

        $collector->collectFromGlobals();

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testPhpExtensionsAreSorted(): void
    {
        $collector = new EnvironmentCollector();
        $collector->startup();
        $collector->collectFromGlobals();

        $data = $collector->getCollected();
        $extensions = $data['php']['extensions'];

        $sorted = $extensions;
        sort($sorted);

        $this->assertSame($sorted, $extensions);

        $collector->shutdown();
    }

    public function testNameDerivation(): void
    {
        $collector = new EnvironmentCollector();

        $this->assertSame('Environment', $collector->getName());
    }
}
