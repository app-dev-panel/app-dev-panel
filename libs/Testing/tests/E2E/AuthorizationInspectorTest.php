<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests for the authorization inspector API endpoint.
 *
 * Tests GET /inspect/api/authorization against a running playground.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group inspector
 */
#[Group('fixtures')]
#[Group('inspector')]
final class AuthorizationInspectorTest extends TestCase
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

        try {
            $response = self::$client->get('/');
            if ($response->getStatusCode() === 0) {
                self::fail(sprintf('Playground server is not running. URL: %s', self::$baseUrl));
            }
        } catch (\Throwable) {
            self::fail(sprintf('Playground server is not running. URL: %s', self::$baseUrl));
        }
    }

    public function testAuthorizationEndpointReturns200(): void
    {
        $response = self::$client->get('/inspect/api/authorization');

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: array{guards: array, roleHierarchy: array, voters: array, config: array}} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'], 'Authorization endpoint should return success=true');
        self::assertIsArray($body['data']);
    }

    public function testAuthorizationResponseHasExpectedStructure(): void
    {
        $response = self::$client->get('/inspect/api/authorization');

        /** @var array{success: bool, data: array{guards: array, roleHierarchy: array, voters: array, config: array}} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $data = $body['data'];

        self::assertArrayHasKey('guards', $data, 'Response should have guards key');
        self::assertArrayHasKey('roleHierarchy', $data, 'Response should have roleHierarchy key');
        self::assertArrayHasKey('voters', $data, 'Response should have voters key');
        self::assertArrayHasKey('config', $data, 'Response should have config key');

        self::assertIsArray($data['guards']);
        self::assertIsArray($data['roleHierarchy']);
        self::assertIsArray($data['voters']);
        self::assertIsArray($data['config']);
    }
}
