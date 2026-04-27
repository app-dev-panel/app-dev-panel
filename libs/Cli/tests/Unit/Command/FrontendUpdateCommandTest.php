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
                ['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/frontend-dist.zip'],
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

        $tester = new CommandTester(new FrontendUpdateCommand($client));
        $tester->execute(['action' => 'check', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('v2.0.0', $decoded['latest_version']);
        $this->assertSame('unknown', $decoded['current_version']);
        $this->assertFalse($decoded['has_frontend_asset']);
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

    public function testDownloadRequiresPath(): void
    {
        $tester = new CommandTester(new FrontendUpdateCommand());
        $tester->execute(['action' => 'download']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Path is required', $tester->getDisplay());
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
        $this->assertStringContainsString('No "frontend-dist.zip" asset found', $display);
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
        $this->assertStringContainsString('No "frontend-dist.zip" asset found', $tester->getDisplay());
    }

    public function testDownloadSuccess(): void
    {
        $tempDir = sys_get_temp_dir() . '/adp-test-download-' . uniqid();

        // Create a valid zip file in memory
        $zipPath = tempnam(sys_get_temp_dir(), 'adp-test-zip-') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('index.html', '<html>test</html>');
        $zip->close();
        $zipContent = file_get_contents($zipPath);
        unlink($zipPath);

        $release = [
            'tag_name' => 'v3.0.0',
            'assets' => [
                ['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/dl.zip'],
            ],
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($release)),
            new Response(200, [], $zipContent),
        ]);

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
            @unlink($tempDir . '/index.html');
            @unlink($tempDir . '/.adp-version');
            @rmdir($tempDir);
        }
    }

    public function testDownloadExtractFailure(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'assets' => [
                ['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/bad.zip'],
            ],
        ];

        // Return invalid zip content
        $client = $this->createMockClient([
            new Response(200, [], json_encode($release)),
            new Response(200, [], 'not-a-zip-file'),
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

    public function testCheckWarnsWhenToolbarMissingFromInstalledPath(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'published_at' => '2026-03-20T10:00:00Z',
            'assets' => [['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/d.zip']],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tempDir = sys_get_temp_dir() . '/adp-test-toolbar-missing-' . uniqid();
        mkdir($tempDir, 0o777, true);
        file_put_contents($tempDir . '/.adp-version', 'v1.0.0');
        // Panel installed, toolbar missing (legacy archive pre-0.3)
        file_put_contents($tempDir . '/index.html', '<html></html>');

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'check', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $display = $tester->getDisplay();
            $this->assertStringContainsString('Toolbar bundle is missing', $display);
        } finally {
            @unlink($tempDir . '/.adp-version');
            @unlink($tempDir . '/index.html');
            @rmdir($tempDir);
        }
    }

    public function testCheckDoesNotWarnWhenToolbarPresent(): void
    {
        $release = [
            'tag_name' => 'v1.0.0',
            'published_at' => '2026-03-20T10:00:00Z',
            'assets' => [['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/d.zip']],
        ];
        $client = $this->createMockClient([new Response(200, [], json_encode($release))]);

        $tempDir = sys_get_temp_dir() . '/adp-test-toolbar-present-' . uniqid();
        mkdir($tempDir . '/toolbar', 0o777, true);
        file_put_contents($tempDir . '/.adp-version', 'v1.0.0');
        file_put_contents($tempDir . '/index.html', '<html></html>');
        file_put_contents($tempDir . '/toolbar/bundle.js', "console.log('t');");

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'check', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $this->assertStringNotContainsString('Toolbar bundle is missing', $tester->getDisplay());
        } finally {
            @unlink($tempDir . '/.adp-version');
            @unlink($tempDir . '/index.html');
            @unlink($tempDir . '/toolbar/bundle.js');
            @rmdir($tempDir . '/toolbar');
            @rmdir($tempDir);
        }
    }

    public function testDownloadExtractsToolbarFromArchive(): void
    {
        $tempDir = sys_get_temp_dir() . '/adp-test-toolbar-dl-' . uniqid();

        $zipPath = tempnam(sys_get_temp_dir(), 'adp-test-zip-') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('index.html', '<html>panel</html>');
        $zip->addFromString('bundle.js', "console.log('panel');");
        $zip->addFromString('toolbar/bundle.js', "console.log('toolbar');");
        $zip->close();
        $zipContent = file_get_contents($zipPath);
        unlink($zipPath);

        $release = [
            'tag_name' => 'v3.0.0',
            'assets' => [['name' => 'frontend-dist.zip', 'browser_download_url' => 'https://example.com/dl.zip']],
        ];
        $client = $this->createMockClient([
            new Response(200, [], json_encode($release)),
            new Response(200, [], $zipContent),
        ]);

        try {
            $tester = new CommandTester(new FrontendUpdateCommand($client));
            $tester->execute(['action' => 'download', '--path' => $tempDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $this->assertFileExists($tempDir . '/toolbar/bundle.js');
            $this->assertStringNotContainsString('Toolbar bundle is missing', $tester->getDisplay());
        } finally {
            @unlink($tempDir . '/index.html');
            @unlink($tempDir . '/bundle.js');
            @unlink($tempDir . '/toolbar/bundle.js');
            @unlink($tempDir . '/.adp-version');
            @rmdir($tempDir . '/toolbar');
            @rmdir($tempDir);
        }
    }

    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }
}
