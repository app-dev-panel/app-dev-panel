<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests that verify the inspector API endpoints work correctly.
 * Tests live inspector actions (EXPLAIN, table list, etc.) against a running playground.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group inspector
 */
#[Group('fixtures')]
#[Group('inspector')]
final class InspectorApiTest extends TestCase
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
                self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::fail(sprintf('Playground server is not running. Start it and re-run. URL: %s', self::$baseUrl));
        }
    }

    public function testTableListReturnsJson(): void
    {
        $response = self::$client->get('/inspect/api/table');

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'] ?? false, 'Response should have success=true');
        self::assertIsArray($body['data']);
    }

    public function testExplainQueryWithValidSql(): void
    {
        // First check that we have at least one table (database is configured)
        $tablesResponse = self::$client->get('/inspect/api/table');
        $tablesBody = json_decode((string) $tablesResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $tables = $tablesBody['data'] ?? [];

        if ($tables === []) {
            self::markTestSkipped('No tables available — database not configured in this playground');
        }

        $tableName = $tables[0]['table'];

        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => sprintf('SELECT * FROM %s LIMIT 1', $tableName),
                'params' => [],
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'] ?? false, 'EXPLAIN response should have success=true');
        self::assertIsArray($body['data'], 'EXPLAIN should return an array of plan rows');
        self::assertNotEmpty($body['data'], 'EXPLAIN plan should not be empty');
    }

    public function testExplainQueryWithParameters(): void
    {
        // First check that we have at least one table
        $tablesResponse = self::$client->get('/inspect/api/table');
        $tablesBody = json_decode((string) $tablesResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $tables = $tablesBody['data'] ?? [];

        if ($tables === []) {
            self::markTestSkipped('No tables available — database not configured in this playground');
        }

        $tableName = $tables[0]['table'];

        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => sprintf('SELECT * FROM %s WHERE 1 = ?', $tableName),
                'params' => [1],
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'] ?? false);
        self::assertIsArray($body['data']);
        self::assertNotEmpty($body['data'], 'EXPLAIN with parameters should return plan rows');
    }

    public function testExplainQueryWithEmptySqlReturns400(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => '',
                'params' => [],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(400, $body['status'] ?? $response->getStatusCode());
    }

    public function testExplainQueryWithInvalidSqlReturns500(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => 'INVALID SQL THAT SHOULD FAIL',
                'params' => [],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        // Should return error status (500) or error in data
        $status = $body['status'] ?? $response->getStatusCode();
        self::assertSame(500, $status, 'Invalid SQL should return 500');
        self::assertArrayHasKey('data', $body);
        self::assertArrayHasKey('error', $body['data']);
    }
}
