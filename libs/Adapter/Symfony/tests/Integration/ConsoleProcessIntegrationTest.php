<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Process-based integration test: runs the actual Symfony bin/console
 * in the playground app and verifies that ADP stores debug data.
 *
 * This test validates the full end-to-end flow:
 *  1. Symfony boots with ADP bundle
 *  2. Console events are intercepted
 *  3. Debugger starts and stops
 *  4. Data is flushed to FileStorage as JSON
 *
 * Requires: playground/symfony-basic-app with `composer install` completed.
 */
#[CoversNothing]
#[Group('playground')]
final class ConsoleProcessIntegrationTest extends TestCase
{
    private const string PLAYGROUND_DIR = __DIR__ . '/../../../../../playground/symfony-basic-app';

    private string $debugStoragePath;

    protected function setUp(): void
    {
        $consolePath = realpath(self::PLAYGROUND_DIR . '/bin/console');
        if ($consolePath === false || !is_executable($consolePath)) {
            $this->markTestSkipped('Playground app not available (bin/console not found or not executable).');
        }

        $vendorPath = realpath(self::PLAYGROUND_DIR . '/vendor/autoload.php');
        if ($vendorPath === false) {
            $this->markTestSkipped(
                'Playground vendor not installed. Run: cd playground/symfony-basic-app && composer install',
            );
        }

        $this->debugStoragePath = realpath(self::PLAYGROUND_DIR) . '/var/debug';

        // Clear previous debug data
        $this->clearDebugStorage();

        // Clear Symfony cache to pick up latest adapter code
        $this->runConsole('cache:clear', '--no-warmup');
        $this->clearDebugStorage(); // cache:clear itself generates debug data
    }

    public function testConsoleCommandProducesDebugData(): void
    {
        $result = $this->runConsole('about');

        $this->assertSame(0, $result['exitCode'], "bin/console about failed:\n" . $result['stderr']);

        // Verify debug storage has a new entry
        $entries = $this->findDebugEntries();
        $this->assertNotEmpty($entries, 'bin/console about should produce debug data in storage');

        $entry = $this->readEntry($entries[0]);

        // Summary must contain the debug id and collector list
        $this->assertArrayHasKey('id', $entry['summary']);
        $this->assertNotEmpty($entry['summary']['id']);
        $this->assertArrayHasKey('collectors', $entry['summary']);
        $this->assertNotEmpty($entry['summary']['collectors']);

        // Data must contain at least the core collectors
        $collectorIds = array_column($entry['summary']['collectors'], 'id');
        $this->assertContains(
            'AppDevPanel\Kernel\Collector\EventCollector',
            $collectorIds,
            'EventCollector must be in the collectors list',
        );
        $this->assertContains(
            'AppDevPanel\Kernel\Collector\Console\CommandCollector',
            $collectorIds,
            'CommandCollector must be in the collectors list',
        );
    }

    public function testConsoleCommandCollectsEvents(): void
    {
        $this->runConsole('about');

        $entries = $this->findDebugEntries();
        $this->assertNotEmpty($entries);

        $entry = $this->readEntry($entries[0]);
        $data = $entry['data'];

        $eventKey = 'AppDevPanel\Kernel\Collector\EventCollector';
        $this->assertArrayHasKey($eventKey, $data, 'Data should contain EventCollector entries');

        $events = $data[$eventKey];
        $this->assertNotEmpty($events, 'Events should be collected during console command');

        // At least the ConsoleTerminateEvent should be present
        $eventNames = array_column($events, 'name');
        $this->assertContains(
            'Symfony\Component\Console\Event\ConsoleTerminateEvent',
            $eventNames,
            'ConsoleTerminateEvent should be captured',
        );
    }

    public function testConsoleCommandCollectsCommandData(): void
    {
        $this->runConsole('about');

        $entries = $this->findDebugEntries();
        $this->assertNotEmpty($entries);

        $entry = $this->readEntry($entries[0]);
        $data = $entry['data'];

        $commandKey = 'AppDevPanel\Kernel\Collector\Console\CommandCollector';
        $this->assertArrayHasKey($commandKey, $data, 'Data should contain CommandCollector entries');

        $commandData = $data[$commandKey];

        // CommandCollector data is keyed by console event class
        $commandEventKey = 'Symfony\Component\Console\Event\ConsoleCommandEvent';
        $this->assertArrayHasKey(
            $commandEventKey,
            $commandData,
            'CommandCollector should contain ConsoleCommandEvent data',
        );
        $this->assertSame('about', $commandData[$commandEventKey]['name']);

        $terminateEventKey = 'Symfony\Component\Console\Event\ConsoleTerminateEvent';
        $this->assertArrayHasKey(
            $terminateEventKey,
            $commandData,
            'CommandCollector should contain ConsoleTerminateEvent data',
        );
        $this->assertSame(0, $commandData[$terminateEventKey]['exitCode']);
    }

    public function testConsoleCommandCollectsLogs(): void
    {
        $result = $this->runConsole('app:test-logging');

        $this->assertSame(0, $result['exitCode'], "app:test-logging failed:\n" . $result['stderr']);

        $entries = $this->findDebugEntries();
        $this->assertNotEmpty($entries, 'app:test-logging should produce debug data');

        $entry = $this->readEntry($entries[0]);
        $data = $entry['data'];

        $logKey = 'AppDevPanel\Kernel\Collector\LogCollector';
        $this->assertArrayHasKey($logKey, $data, 'Data should contain LogCollector entries');

        $logs = $data[$logKey];
        $this->assertNotEmpty($logs, 'Logs should be collected during console command');

        // Verify the 3 test log messages are captured
        $messages = array_column($logs, 'message');
        $this->assertContains('Test log message from console command', $messages);
        $this->assertContains('This is a warning log', $messages);
        $this->assertContains('This is an error log', $messages);

        // Verify log levels
        $logsByMessage = [];
        foreach ($logs as $log) {
            $logsByMessage[$log['message']] = $log;
        }
        $this->assertSame('info', $logsByMessage['Test log message from console command']['level']);
        $this->assertSame('warning', $logsByMessage['This is a warning log']['level']);
        $this->assertSame('error', $logsByMessage['This is an error log']['level']);
    }

    public function testIgnoredCommandProducesNoDebugData(): void
    {
        // 'list' is in the default ignored_commands
        $this->runConsole('list');

        $entries = $this->findDebugEntries();
        $this->assertEmpty($entries, 'Ignored command "list" should not produce debug data');
    }

    /**
     * Runs bin/console with the given command and arguments.
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runConsole(string $command, string ...$args): array
    {
        $consolePath = realpath(self::PLAYGROUND_DIR . '/bin/console');
        $cmd = sprintf(
            'php %s %s %s 2>&1',
            escapeshellarg($consolePath),
            escapeshellarg($command),
            implode(' ', array_map('escapeshellarg', $args)),
        );

        $process = proc_open(
            $cmd,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            realpath(self::PLAYGROUND_DIR),
            ['APP_ENV' => 'dev', 'APP_DEBUG' => '1'],
        );

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return ['exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * Finds all debug entry directories in storage.
     *
     * @return string[] Absolute paths to entry directories
     */
    private function findDebugEntries(): array
    {
        if (!is_dir($this->debugStoragePath)) {
            return [];
        }

        $entries = [];
        $dateDirs = glob($this->debugStoragePath . '/*', GLOB_ONLYDIR);
        foreach ($dateDirs as $dateDir) {
            $entryDirs = glob($dateDir . '/*', GLOB_ONLYDIR);
            foreach ($entryDirs as $entryDir) {
                if (!file_exists($entryDir . '/summary.json.gz') && !file_exists($entryDir . '/summary.json')) {
                    continue;
                }

                $entries[] = $entryDir;
            }
        }

        return $entries;
    }

    /**
     * Reads a debug entry (summary + data) from storage.
     *
     * @return array{summary: array, data: array}
     */
    private function readEntry(string $entryDir): array
    {
        return [
            'summary' => json_decode(self::readStorageFile($entryDir . '/summary'), true, 512, JSON_THROW_ON_ERROR),
            'data' => json_decode(self::readStorageFile($entryDir . '/data'), true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    private static function readStorageFile(string $basePath): string
    {
        if (file_exists($basePath . '.json.gz')) {
            return gzdecode(file_get_contents($basePath . '.json.gz'));
        }

        return file_get_contents($basePath . '.json');
    }

    private function clearDebugStorage(): void
    {
        if (!is_dir($this->debugStoragePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->debugStoragePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
    }
}
