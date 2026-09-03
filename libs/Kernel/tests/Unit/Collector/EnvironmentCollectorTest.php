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

    public function testSensitiveServerParamsAreRedactedByDefault(): void
    {
        $collector = new EnvironmentCollector();
        $collector->startup();
        $collector->collectFromRequest($this->requestWithServerParams([
            'SERVER_NAME' => 'localhost',
            'DB_PASSWORD' => 'hunter2',
            'db_pass' => 'lower',
            'API_KEY' => 'k',
            'GITHUB_TOKEN' => 't',
            'DATABASE_DSN' => 'mysql://root:pw@db',
            'AWS_SECRET_ACCESS_KEY' => 's',
            'HTTP_AUTHORIZATION' => 'Bearer abc',
            'APP_PRIVATE_PEM' => 'pem',
            'SMTP_CREDENTIALS' => 'c',
            'REQUEST_URI' => '/test',
        ]));

        $server = $collector->getCollected()['server'];

        $this->assertSame('localhost', $server['SERVER_NAME']);
        $this->assertSame('/test', $server['REQUEST_URI']);
        foreach ([
            'DB_PASSWORD',
            'db_pass',
            'API_KEY',
            'GITHUB_TOKEN',
            'DATABASE_DSN',
            'AWS_SECRET_ACCESS_KEY',
            'HTTP_AUTHORIZATION',
            'APP_PRIVATE_PEM',
            'SMTP_CREDENTIALS',
        ] as $key) {
            $this->assertArrayHasKey($key, $server, 'Key names must be preserved');
            $this->assertSame(EnvironmentCollector::REDACTED, $server[$key], $key . ' must be redacted');
        }
    }

    public function testSensitiveEnvironmentVariablesAreRedacted(): void
    {
        putenv('ADP_TEST_SECRET=hidden');
        putenv('ADP_TEST_PLAIN=visible');
        try {
            $collector = new EnvironmentCollector();
            $collector->startup();
            $collector->collectFromRequest($this->requestWithServerParams([]));
            $env = $collector->getCollected()['env'];
        } finally {
            putenv('ADP_TEST_SECRET');
            putenv('ADP_TEST_PLAIN');
        }

        $this->assertSame(EnvironmentCollector::REDACTED, $env['ADP_TEST_SECRET']);
        $this->assertSame('visible', $env['ADP_TEST_PLAIN']);
    }

    public function testSensitiveKeyPatternIsConfigurable(): void
    {
        $collector = new EnvironmentCollector(sensitiveKeyPattern: '/^ONLY_THIS$/');
        $collector->startup();
        $collector->collectFromRequest($this->requestWithServerParams([
            'ONLY_THIS' => 'x',
            'DB_PASSWORD' => 'kept-because-pattern-overridden',
        ]));

        $server = $collector->getCollected()['server'];

        $this->assertSame(EnvironmentCollector::REDACTED, $server['ONLY_THIS']);
        $this->assertSame('kept-because-pattern-overridden', $server['DB_PASSWORD']);
    }

    public function testNullPatternDisablesRedaction(): void
    {
        $collector = new EnvironmentCollector(sensitiveKeyPattern: null);
        $collector->startup();
        $collector->collectFromRequest($this->requestWithServerParams(['DB_PASSWORD' => 'raw']));

        $this->assertSame('raw', $collector->getCollected()['server']['DB_PASSWORD']);
    }

    public function testDefaultPatternMatchesDocumentedKeywords(): void
    {
        foreach (['PASS', 'PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'DSN', 'CREDENTIAL', 'PRIVATE', 'AUTH'] as $word) {
            $this->assertSame(1, preg_match(EnvironmentCollector::DEFAULT_SENSITIVE_KEY_PATTERN, 'X_' . $word . '_Y'));
            $this->assertSame(1, preg_match(EnvironmentCollector::DEFAULT_SENSITIVE_KEY_PATTERN, strtolower($word)));
        }
        $this->assertSame(0, preg_match(EnvironmentCollector::DEFAULT_SENSITIVE_KEY_PATTERN, 'APP_ENV'));
    }

    public function testHangingGitCommandIsKilledWithinTimeout(): void
    {
        $collector = new EnvironmentCollector(gitTimeout: 0.3, gitBinary: $this->phpCommand('sleep(30);'));
        $collector->startup();

        $start = microtime(true);
        $git = $collector->getCollected()['git'];
        $elapsed = microtime(true) - $start;

        $this->assertSame(['branch' => null, 'commit' => null, 'commitFull' => null], $git);
        // Three git invocations, 0.3s each, plus process spawn overhead.
        $this->assertLessThan(3.0, $elapsed, 'git subprocess must be killed at the deadline');
    }

    public function testChattyStderrDoesNotDeadlockAndStdoutIsReturned(): void
    {
        // 200 KiB on stderr exceeds the pipe buffer: without draining it the child blocks forever.
        $code = 'fwrite(STDERR, str_repeat("e", 200000)); echo "main-output";';
        $collector = new EnvironmentCollector(gitTimeout: 5.0, gitBinary: $this->phpCommand($code));
        $collector->startup();

        $git = $collector->getCollected()['git'];

        $this->assertSame('main-output', $git['branch']);
        $this->assertSame('main-output', $git['commit']);
        $this->assertSame('main-output', $git['commitFull']);
    }

    public function testNonZeroExitCodeYieldsNull(): void
    {
        $collector = new EnvironmentCollector(gitBinary: $this->phpCommand('echo "x"; exit(128);'));
        $collector->startup();

        $this->assertNull($collector->getCollected()['git']['branch']);
    }

    public function testGitInfoIsMemoisedPerRequestAndResetOnStartup(): void
    {
        $counterFile = tempnam(sys_get_temp_dir(), 'adp-git-');
        $this->assertNotFalse($counterFile);
        $code = sprintf('file_put_contents(%s, "1", FILE_APPEND); echo "abc";', var_export($counterFile, true));
        $collector = new EnvironmentCollector(gitBinary: $this->phpCommand($code));

        try {
            $collector->startup();
            $collector->getCollected();
            $collector->getSummary();
            $this->assertSame(3, strlen((string) file_get_contents($counterFile)), 'three git calls per request');

            $collector->startup();
            $collector->getSummary();
            $this->assertSame(6, strlen((string) file_get_contents($counterFile)), 'cache is reset by startup()');
        } finally {
            unlink($counterFile);
        }
    }

    private function phpCommand(string $code): string
    {
        return escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' --';
    }

    private function requestWithServerParams(array $serverParams): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn($serverParams);

        return $request;
    }
}
