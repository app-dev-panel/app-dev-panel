<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\FrontendUpdateCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FrontendUpdateCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $command = new FrontendUpdateCommand();

        $this->assertSame('frontend:update', $command->getName());
        $this->assertSame('Check for updates and download the latest frontend build', $command->getDescription());
    }

    public function testCommandHasOptions(): void
    {
        $command = new FrontendUpdateCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('path'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasArgument('action'));
    }

    public function testUnknownAction(): void
    {
        $tester = new CommandTester(new FrontendUpdateCommand());
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }

    public function testCheckSuccess(): void
    {
        $release = [
            'tag_name' => 'v1.2.0',
            'published_at' => '2026-03-15T10:00:00Z',
            'assets' => [
                ['name' => 'panel-dist.tar.gz', 'browser_download_url' => 'https://example.com/panel-dist.tar.gz'],
            ],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'check']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Frontend Update Check', $display);
        $this->assertStringContainsString('v1.2.0', $display);
        $this->assertStringContainsString('Yes', $display); // has asset
    }

    public function testCheckJson(): void
    {
        $release = [
            'tag_name' => 'v2.0.0',
            'published_at' => '2026-03-20T10:00:00Z',
            'assets' => [],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tempDir = sys_get_temp_dir() . '/adp-test-checkjson-' . uniqid();

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'check', '--json' => true, '--path' => $tempDir]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('v2.0.0', $decoded['latest_version']);
        $this->assertSame('unknown', $decoded['current_version']);
        $this->assertFalse($decoded['has_frontend_asset']);
        $this->assertArrayHasKey('frontend_path', $decoded);
    }

    public function testCheckWithUpdateAvailable(): void
    {
        $release = [
            'tag_name' => 'v2.0.0',
            'published_at' => '2026-03-20T10:00:00Z',
            'assets' => [],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tempDir = sys_get_temp_dir() . '/adp-test-frontend-' . uniqid();
        mkdir($tempDir, 0o777, true);
        file_put_contents($tempDir . '/.adp-version', 'v1.0.0');

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'check', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $display = $tester->getDisplay();
            $this->assertStringContainsString('Update available', $display);
            $this->assertStringContainsString('v1.0.0', $display);
            $this->assertStringContainsString('v2.0.0', $display);
        } finally {
            @unlink($tempDir . '/.adp-version');
            @rmdir($tempDir);
        }
    }

    public function testCheckAlreadyUpToDate(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'published_at' => '2026-03-20T10:00:00Z',
            'assets' => [],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tempDir = sys_get_temp_dir() . '/adp-test-frontend-' . uniqid();
        mkdir($tempDir, 0o777, true);
        file_put_contents($tempDir . '/.adp-version', 'v1.0.0');

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'check', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $this->assertStringContainsString('Already up to date', $tester->getDisplay());
        } finally {
            @unlink($tempDir . '/.adp-version');
            @rmdir($tempDir);
        }
    }

    public function testCheckNetworkError(): void
    {
        $client = $this->createMockClient([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('GET', '/test'),
            ),
        ]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'check']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Failed to check for updates', $tester->getDisplay());
    }

    public function testDownloadUsesDefaultPathWhenNotSpecified(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'assets' => [],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'download']);

        // Should not error about missing path — uses default
        $display = $tester->getDisplay();
        $this->assertStringNotContainsString('Path is required', $display);
    }

    public function testDownloadNetworkError(): void
    {
        $client = $this->createMockClient([
            new \GuzzleHttp\Exception\ConnectException('Network error', new \GuzzleHttp\Psr7\Request('GET', '/test')),
        ]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'download', '--path' => '/tmp/test']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Failed to fetch release info', $tester->getDisplay());
    }

    public function testDownloadNoAsset(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'assets' => [
                ['name' => 'other-file.tar.gz'],
            ],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'download', '--path' => '/tmp/test']);

        $this->assertSame(1, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('No "panel-dist.tar.gz" asset found', $display);
        $this->assertStringContainsString('other-file.tar.gz', $display);
    }

    public function testDownloadNoAssetEmptyAssets(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'assets' => [],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'download', '--path' => '/tmp/test']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('No "panel-dist.tar.gz" asset found', $tester->getDisplay());
    }

    public function testDownloadSuccess(): void
    {
        $tempDir = sys_get_temp_dir() . '/adp-test-download-' . uniqid();

        // Create a valid tar.gz file with dist/ directory structure
        $tarPath = $this->createTestTarGz(['dist/index.html' => '<html>test</html>']);

        $release = [
            'tag_name' => 'v3.0.0',
            'assets' => [
                ['name' => 'panel-dist.tar.gz', 'browser_download_url' => 'https://example.com/panel.tar.gz'],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($release)),
            new Response(200, [], file_get_contents($tarPath)),
        ]);
        unlink($tarPath);

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'download', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $display = $tester->getDisplay();
            $this->assertStringContainsString('Frontend updated to v3.0.0', $display);
            $this->assertStringContainsString('Extracted to', $display);
            $this->assertFileExists($tempDir . '/.adp-version');
            $this->assertSame('v3.0.0', file_get_contents($tempDir . '/.adp-version'));
            $this->assertFileExists($tempDir . '/index.html');
        } finally {
            $this->removeDir($tempDir);
        }
    }

    public function testDownloadExtractFailure(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'assets' => [
                ['name' => 'panel-dist.tar.gz', 'browser_download_url' => 'https://example.com/bad.tar.gz'],
            ],
        ];

        // Return invalid tar.gz content
        $client = $this->createMockClient([
            new Response(200, [], json_encode($release)),
            new Response(200, [], 'not-a-tar-file'),
        ]);

        $tempDir = sys_get_temp_dir() . '/adp-test-badfail-' . uniqid();

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'download', '--path' => $tempDir]);

            $this->assertSame(1, $tester->getStatusCode());
            $this->assertStringContainsString('Download failed', $tester->getDisplay());
        } finally {
            @rmdir($tempDir);
        }
    }

    /**
     * @param array<string, string> $files
     */
    private function createTestTarGz(array $files): string
    {
        $tarDir = sys_get_temp_dir() . '/adp-test-tar-' . uniqid();
        mkdir($tarDir, 0o777, true);

        foreach ($files as $name => $content) {
            $filePath = $tarDir . '/' . $name;
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0o777, true);
            }
            file_put_contents($filePath, $content);
        }

        $tarPath = sys_get_temp_dir() . '/adp-test-' . uniqid() . '.tar';
        $tarGzPath = $tarPath . '.gz';

        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory($tarDir);
        $phar->compress(\Phar::GZ);

        // Clean up
        unlink($tarPath);
        $this->removeDir($tarDir);

        return $tarGzPath;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }
}
