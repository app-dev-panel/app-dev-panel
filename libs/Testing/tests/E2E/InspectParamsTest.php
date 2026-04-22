<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * E2E tests for the inspector parameters endpoint.
 *
 * Tests GET /inspect/api/params against a running playground. Catches the
 * regression where an adapter wires InspectController without injecting the
 * application parameters, causing the frontend Configuration > Parameters tab
 * to render "No parameters found".
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8101 php vendor/bin/phpunit --testsuite Fixtures --group inspector
 */
#[Group('fixtures')]
#[Group('inspector')]
final class InspectParamsTest extends TestCase
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
            'timeout' => 1,
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

    public function testParamsEndpointReturns200(): void
    {
        $response = self::$client->get('/inspect/api/params');

        self::assertSame(200, $response->getStatusCode());

        /** @var array{success: bool, data: array<string, mixed>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertTrue($body['success'], 'Params endpoint should return success=true');
        self::assertIsArray($body['data']);
    }

    public function testParamsResponseIsNonEmpty(): void
    {
        $response = self::$client->get('/inspect/api/params');

        /** @var array{success: bool, data: array<string, mixed>} $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty(
            $body['data'],
            'Inspector params should expose application configuration. '
            . 'An empty payload means the adapter forgot to inject params into InspectController.',
        );
    }
}
