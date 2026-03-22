<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use AppDevPanel\Testing\Runner\FixtureRunner;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end scenario test: fires ALL fixtures, then verifies the full pipeline.
 *
 * Flow:
 * 1. Clear debug storage (via dedicated test endpoint)
 * 2. Fire ALL fixtures in bulk
 * 3. Verify /debug/api/ lists all entries
 * 4. For each entry, verify /debug/api/view/{id} returns collector data
 * 5. Verify /debug/api/summary/{id} contains adapter name and PHP version
 * 6. Verify each fixture's expectations against the stored data
 *
 * Run:
 *   PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group scenario
 */
#[Group('fixtures')]
#[Group('scenario')]
final class ScenarioTest extends FixtureTestCase
{
    private static Client $client;

    /** @var array<string, string> fixture name => debug ID */
    private static array $fixtureDebugIds = [];

    /** @var array<string, array<string, mixed>> debug ID => full data */
    private static array $debugDataCache = [];

    /** @var list<array<string, mixed>> all summary entries */
    private static array $summaryEntries = [];

    /** @var array<string, mixed> result of CLI reset */
    private static array $cliResetResult = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$client = new Client([
            'base_uri' => self::$baseUrl,
            'http_errors' => false,
            'timeout' => 15,
        ]);

        // Step 1: Clear debug storage
        self::clearDebugStorage();

        // Step 2: Fire ALL fixtures and collect debug IDs
        self::fireAllFixtures();

        // Step 3: Load summary list
        self::loadSummaryEntries();
    }

    // =========================================================================
    // Pipeline verification tests
    // =========================================================================

    public function testCliResetCommandSucceeded(): void
    {
        self::assertNotEmpty(self::$cliResetResult, 'CLI reset endpoint did not return a response');
        self::assertSame(
            'ok',
            self::$cliResetResult['status'] ?? null,
            sprintf(
                'CLI debug:reset failed (exit code: %s, error: %s)',
                self::$cliResetResult['exitCode'] ?? 'unknown',
                self::$cliResetResult['error'] ?? self::$cliResetResult['output'] ?? 'unknown',
            ),
        );
        self::assertSame(0, self::$cliResetResult['exitCode'] ?? -1);
    }

    public function testStorageWasCleared(): void
    {
        // We should have entries only from fixtures we just fired
        $fixtureCount = count(FixtureRegistry::all());
        $entryCount = count(self::$summaryEntries);

        // At least as many entries as fixtures (some fixtures share endpoints so IDs may differ)
        self::assertGreaterThanOrEqual(
            $fixtureCount,
            $entryCount,
            sprintf('Expected at least %d debug entries (one per fixture), got %d', $fixtureCount, $entryCount),
        );
    }

    public function testAllFixturesProducedDebugIds(): void
    {
        $missing = [];
        foreach (FixtureRegistry::all() as $fixture) {
            if (isset(self::$fixtureDebugIds[$fixture->name])) {
                continue;
            }

            $missing[] = $fixture->name;
        }

        self::assertEmpty($missing, sprintf('Fixtures without debug IDs: %s', implode(', ', $missing)));
    }

    public function testDebugApiListContainsAllEntries(): void
    {
        $listedIds = array_column(self::$summaryEntries, 'id');

        foreach (self::$fixtureDebugIds as $fixtureName => $debugId) {
            self::assertContains(
                $debugId,
                $listedIds,
                sprintf("Debug ID '%s' from fixture '%s' not found in /debug/api/ listing", $debugId, $fixtureName),
            );
        }
    }

    public function testSummaryEntriesHaveRequiredFields(): void
    {
        foreach (self::$summaryEntries as $entry) {
            self::assertIsArray($entry);
            self::assertArrayHasKey('id', $entry, 'Summary entry must have id');
            self::assertArrayHasKey('collectors', $entry, 'Summary entry must have collectors list');
            self::assertIsArray($entry['collectors']);
        }
    }

    public function testSummaryContainsAdapterName(): void
    {
        // At least one web entry should have adapter name in summary
        $foundAdapter = false;
        foreach (self::$summaryEntries as $entry) {
            $web = $entry['web'] ?? null;
            if (is_array($web) && isset($web['adapter']) && is_string($web['adapter']) && $web['adapter'] !== '') {
                $foundAdapter = true;
                self::assertContains(
                    $web['adapter'],
                    ['Yii3', 'Symfony', 'Yii2'],
                    sprintf('Unexpected adapter name: %s', $web['adapter']),
                );
                break;
            }
        }

        self::assertTrue($foundAdapter, 'No summary entry contains adapter name in web.adapter');
    }

    public function testSummaryContainsPhpVersion(): void
    {
        $foundPhp = false;
        foreach (self::$summaryEntries as $entry) {
            $environment = $entry['environment'] ?? null;
            if (is_array($environment) && isset($environment['php']['version'])) {
                $foundPhp = true;
                self::assertSame(PHP_VERSION, $environment['php']['version']);
                break;
            }
        }

        self::assertTrue($foundPhp, 'No summary entry contains PHP version');
    }

    public function testViewEndpointReturnsDataForEachEntry(): void
    {
        $checked = 0;
        foreach (self::$fixtureDebugIds as $fixtureName => $debugId) {
            $data = self::getDebugData($debugId);
            self::assertNotNull($data, sprintf(
                "Could not fetch data for fixture '%s' (ID: %s)",
                $fixtureName,
                $debugId,
            ));
            self::assertNotEmpty($data, sprintf("Empty data for fixture '%s' (ID: %s)", $fixtureName, $debugId));
            $checked++;
        }

        self::assertGreaterThan(0, $checked, 'No entries were checked');
    }

    public function testViewEndpointDataContainsCollectorKeys(): void
    {
        // Pick first fixture with data
        foreach (self::$fixtureDebugIds as $debugId) {
            $data = self::getDebugData($debugId);
            if ($data === null || $data === []) {
                continue;
            }

            // Keys should be collector FQCNs
            foreach (array_keys($data) as $key) {
                self::assertIsString($key);
                self::assertStringContainsString(
                    'Collector',
                    $key,
                    sprintf('Collector key "%s" should contain "Collector" (FQCN format)', $key),
                );
            }

            return;
        }

        self::fail('No debug entries with collector data found');
    }

    public function testViewWithCollectorFilterReturnsFilteredData(): void
    {
        // Find a debug ID that has log data
        $logFixtureId = self::$fixtureDebugIds['logs:basic'] ?? null;
        if ($logFixtureId === null) {
            self::markTestSkipped('logs:basic fixture not available');
        }

        $data = self::getDebugData($logFixtureId);
        self::assertNotNull($data);

        // Find LogCollector key
        $logKey = null;
        foreach (array_keys($data) as $key) {
            if (!str_contains($key, 'LogCollector')) {
                continue;
            }

            $logKey = $key;
            break;
        }
        self::assertNotNull($logKey, 'LogCollector not found in data');

        // Fetch with collector filter
        $response = self::$client->get(sprintf('/debug/api/view/%s?collector=%s', $logFixtureId, urlencode($logKey)));
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $filtered = $body['data'] ?? $body;
        self::assertIsArray($filtered);
        self::assertNotEmpty($filtered, 'Filtered collector data should not be empty');
    }

    // =========================================================================
    // Per-fixture expectation tests (all fixtures, all expectations)
    // =========================================================================

    public function testLogsBasicFixture(): void
    {
        $this->assertFixtureExpectations('logs:basic');
    }

    public function testLogsContextFixture(): void
    {
        $this->assertFixtureExpectations('logs:context');
    }

    public function testEventsBasicFixture(): void
    {
        $this->assertFixtureExpectations('events:basic');
    }

    public function testVarDumperBasicFixture(): void
    {
        $this->assertFixtureExpectations('var-dumper:basic');
    }

    public function testTimelineBasicFixture(): void
    {
        $this->assertFixtureExpectations('timeline:basic');
    }

    public function testRequestBasicFixture(): void
    {
        $this->assertFixtureExpectations('request:basic');
    }

    public function testWebAppInfoFixture(): void
    {
        $this->assertFixtureExpectations('web:app-info');
    }

    public function testExceptionRuntimeFixture(): void
    {
        $this->assertFixtureExpectations('exception:runtime');
    }

    public function testExceptionChainedFixture(): void
    {
        $this->assertFixtureExpectations('exception:chained');
    }

    public function testMultiLogsAndEventsFixture(): void
    {
        $this->assertFixtureExpectations('multi:logs-and-events');
    }

    public function testLogsHeavyFixture(): void
    {
        $this->assertFixtureExpectations('logs:heavy');
    }

    public function testHttpClientBasicFixture(): void
    {
        $this->assertFixtureExpectations('http-client:basic');
    }

    public function testFilesystemBasicFixture(): void
    {
        $this->assertFixtureExpectations('filesystem:basic');
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function assertFixtureExpectations(string $fixtureName): void
    {
        $fixture = self::findFixture($fixtureName);
        self::assertNotNull($fixture, sprintf("Fixture '%s' not found in registry", $fixtureName));

        $debugId = self::$fixtureDebugIds[$fixtureName] ?? null;
        if ($debugId === null) {
            self::fail(sprintf("No debug ID for fixture '%s' — fixture did not produce an entry", $fixtureName));
        }

        // Re-run evaluation using FixtureRunner's logic
        $result = self::$runner->run($fixture);

        if ($result->error !== null) {
            self::fail(sprintf("Fixture '%s' error: %s", $fixtureName, $result->error));
        }

        $failures = [];
        foreach ($result->assertions as $assertion) {
            if ($assertion->passed) {
                continue;
            }

            $failures[] = $assertion->message;
        }

        self::assertTrue(
            $result->passed,
            sprintf(
                "Fixture '%s' expectations failed (debug ID: %s):\n  - %s",
                $fixtureName,
                $result->debugId ?? $debugId,
                implode("\n  - ", $failures),
            ),
        );
    }

    private static function findFixture(string $name): ?\AppDevPanel\Testing\Fixture\Fixture
    {
        foreach (FixtureRegistry::all() as $fixture) {
            if ($fixture->name === $name) {
                return $fixture;
            }
        }

        return null;
    }

    private static function clearDebugStorage(): void
    {
        // Clear via direct storage call
        $response = self::$client->get('/test/fixtures/reset');
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            usleep(200_000);
        }

        // Also clear via CLI command (debug:reset) executed server-side
        $cliResponse = self::$client->get('/test/fixtures/reset-cli');
        if ($cliResponse->getStatusCode() >= 200 && $cliResponse->getStatusCode() < 300) {
            /** @var array<string, mixed> $body */
            $body = json_decode((string) $cliResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::$cliResetResult = $body;
            usleep(200_000);
        }
    }

    private static function fireAllFixtures(): void
    {
        $runner = new FixtureRunner(self::$baseUrl, retryDelayMs: 300, maxRetries: 15);

        foreach (FixtureRegistry::all() as $fixture) {
            $result = $runner->run($fixture);
            if ($result->debugId !== null) {
                self::$fixtureDebugIds[$fixture->name] = $result->debugId;
            }
            // Small delay between fixtures for storage writes
            usleep(100_000);
        }
    }

    private static function loadSummaryEntries(): void
    {
        // Retry a few times to let storage writes complete
        for ($i = 0; $i < 5; $i++) {
            $entries = self::fetchApiEntries();

            if (count($entries) >= count(self::$fixtureDebugIds)) {
                self::$summaryEntries = $entries;
                return;
            }

            usleep(500_000);
        }

        // Use whatever we got
        self::$summaryEntries = self::fetchApiEntries();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function fetchApiEntries(): array
    {
        $response = self::$client->get('/debug/api/');
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $body['data'] ?? $body;

        if (!is_array($entries)) {
            return [];
        }

        /** @var list<array<string, mixed>> */
        return array_values(array_filter($entries, 'is_array'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function getDebugData(string $debugId): ?array
    {
        if (isset(self::$debugDataCache[$debugId])) {
            return self::$debugDataCache[$debugId];
        }

        for ($i = 0; $i < 10; $i++) {
            $response = self::$client->get(sprintf('/debug/api/view/%s', $debugId));
            if ($response->getStatusCode() === 200) {
                /** @var array<string, mixed> $body */
                $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                /** @var array<string, mixed> $data */
                $data = is_array($body['data'] ?? null) ? $body['data'] : $body;
                self::$debugDataCache[$debugId] = $data;
                return $data;
            }
            usleep(200_000);
        }

        return null;
    }
}
