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
 * Requires a playground with SQLite configured (test_users table with seed data).
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

        // Verify database inspector is available (requires SchemaProvider)
        $tableResponse = self::$client->get('/inspect/api/table');
        if ($tableResponse->getStatusCode() !== 200) {
            self::markTestSkipped('Database inspector not available on this playground');
        }
    }

    public function testTableListReturnsTablesIncludingTestUsers(): void
    {
        $response = self::$client->get('/inspect/api/table');

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: list<array{table: string, records: int}>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'], 'Response should have success=true');
        self::assertIsArray($body['data']);
        self::assertNotEmpty($body['data'], 'Table list should not be empty — SQLite DB must be configured');

        $tableNames = array_column($body['data'], 'table');
        self::assertContains('test_users', $tableNames, 'test_users table should exist');
    }

    public function testExplainSelectFromTestUsers(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => 'SELECT * FROM test_users LIMIT 1',
                'params' => [],
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: list<array<string, mixed>>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'], 'EXPLAIN response should have success=true');
        self::assertIsArray($body['data'], 'EXPLAIN should return an array of plan rows');
        self::assertNotEmpty($body['data'], 'EXPLAIN plan should not be empty');
    }

    public function testExplainSelectWithWhereAndParameters(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => 'SELECT * FROM test_users WHERE id = ?',
                'params' => [1],
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: list<array<string, mixed>>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success']);
        self::assertIsArray($body['data']);
        self::assertNotEmpty($body['data'], 'EXPLAIN with parameters should return plan rows');
    }

    public function testExplainSelectWithNamedParameters(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => 'SELECT * FROM test_users WHERE name = :name',
                'params' => ['name' => 'Alice'],
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: list<array<string, mixed>>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success']);
        self::assertIsArray($body['data']);
        self::assertNotEmpty($body['data'], 'EXPLAIN with named parameters should return plan rows');
    }

    public function testExplainWithEmptySqlReturns400(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => '',
                'params' => [],
            ],
        ]);

        self::assertSame(400, $response->getStatusCode());

        /** @var array{success: bool, data: array{error: string}} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($body['success']);
        self::assertIsArray($body['data']);
    }

    public function testExplainWithInvalidSqlReturns500(): void
    {
        $response = self::$client->post('/inspect/api/table/explain', [
            'json' => [
                'sql' => 'INVALID SQL THAT SHOULD FAIL',
                'params' => [],
            ],
        ]);

        self::assertSame(500, $response->getStatusCode());

        /** @var array{success: bool, data: array{error: string}} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertFalse($body['success'], 'Invalid SQL should fail');
        self::assertArrayHasKey('error', $body['data'], 'Error message should be present in data');
    }
}
