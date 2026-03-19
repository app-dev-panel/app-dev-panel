<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests that verify the debug API endpoints work correctly.
 * Tests the API contract independent of specific collectors.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group api
 */
#[Group('fixtures')]
#[Group('api')]
final class DebugApiTest extends TestCase
{
    private static Client $client;
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$baseUrl = rtrim((string) getenv('PLAYGROUND_URL') ?: 'http://127.0.0.1:8080', '/');
        self::$client = new Client([
            'base_uri' => self::$baseUrl,
            'http_errors' => false,
            'timeout' => 10,
        ]);

        // Verify server reachable
        try {
            $response = self::$client->get('/');
            if ($response->getStatusCode() === 0) {
                self::markTestSkipped(sprintf('Server not reachable at %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::markTestSkipped(sprintf('Server not reachable at %s', self::$baseUrl));
        }
    }

    public function testDebugApiListReturnsJson(): void
    {
        $response = self::$client->get('/debug/api/');

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
    }

    public function testDebugApiListAfterRequest(): void
    {
        // Generate a debug entry by hitting the app
        self::$client->get('/test/fixtures/logs');

        // Wait briefly for async storage write
        usleep(500_000);

        $response = self::$client->get('/debug/api/');
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $body['data'] ?? $body;

        self::assertIsArray($entries);
        self::assertNotEmpty($entries, 'Debug API should return at least one entry after a request');
    }

    public function testDebugApiViewReturnsEntryData(): void
    {
        // Generate a debug entry
        self::$client->get('/test/fixtures/request-info');
        usleep(500_000);

        // Get latest entry
        $listResponse = self::$client->get('/debug/api/');
        $listBody = json_decode((string) $listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $listBody['data'] ?? $listBody;

        self::assertNotEmpty($entries);

        $latestId = is_array(reset($entries)) ? reset($entries)['id'] : null;
        self::assertNotNull($latestId, 'Latest entry must have an ID');

        // Fetch full data
        $viewResponse = self::$client->get(sprintf('/debug/api/view/%s', $latestId));
        self::assertSame(200, $viewResponse->getStatusCode());

        $viewBody = json_decode((string) $viewResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($viewBody);
    }

    public function testDebugApiViewWithCollectorFilter(): void
    {
        // Generate a debug entry with logs
        self::$client->get('/test/fixtures/logs');
        usleep(500_000);

        // Get latest entry
        $listResponse = self::$client->get('/debug/api/');
        $listBody = json_decode((string) $listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $listBody['data'] ?? $listBody;

        self::assertNotEmpty($entries);
        $latestId = reset($entries)['id'] ?? null;
        self::assertNotNull($latestId);

        // Fetch with collector filter — find a LogCollector key
        $viewResponse = self::$client->get(sprintf('/debug/api/view/%s', $latestId));
        $viewBody = json_decode((string) $viewResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $data = $viewBody['data'] ?? $viewBody;

        // Find a key containing "LogCollector"
        $logCollectorKey = null;
        foreach (array_keys($data) as $key) {
            if (str_contains((string) $key, 'LogCollector')) {
                $logCollectorKey = $key;
                break;
            }
        }

        if ($logCollectorKey === null) {
            self::fail('LogCollector not found in debug data');
        }

        $filteredResponse = self::$client->get(sprintf(
            '/debug/api/view/%s?collector=%s',
            $latestId,
            urlencode($logCollectorKey),
        ));
        self::assertSame(200, $filteredResponse->getStatusCode());

        $filteredBody = json_decode((string) $filteredResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($filteredBody);
    }

    public function testDebugApiViewNotFound(): void
    {
        $response = self::$client->get('/debug/api/view/nonexistent-id-12345');

        // Should return 404 or error
        self::assertContains($response->getStatusCode(), [404, 500]);
    }

    public function testDebugApiSummary(): void
    {
        // Generate a debug entry
        self::$client->get('/test/fixtures/request-info');
        usleep(500_000);

        // Get latest
        $listResponse = self::$client->get('/debug/api/');
        $listBody = json_decode((string) $listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entries = $listBody['data'] ?? $listBody;

        self::assertNotEmpty($entries);
        $latestId = reset($entries)['id'] ?? null;
        self::assertNotNull($latestId);

        $summaryResponse = self::$client->get(sprintf('/debug/api/summary/%s', $latestId));
        self::assertSame(200, $summaryResponse->getStatusCode());

        $summaryBody = json_decode((string) $summaryResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($summaryBody);
    }
}
