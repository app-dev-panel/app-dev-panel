<?php

declare(strict_types=1);

namespace App\Tests;

use OpenApi\Generator;
use PHPUnit\Framework\TestCase;

final class OpenApiTest extends TestCase
{
    public function testSpecIsGenerated(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);

        $json = $openapi->toJson();
        self::assertJson($json);

        $spec = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($spec);
        self::assertArrayHasKey('openapi', $spec);
        self::assertArrayHasKey('info', $spec);
        self::assertArrayHasKey('paths', $spec);
    }

    public function testSpecContainsCorrectInfo(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('ADP Laravel Playground API', $spec['info']['title']);
        self::assertSame('1.0.0', $spec['info']['version']);
    }

    public function testSpecContainsApiPaths(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('/api', $spec['paths']);
        self::assertArrayHasKey('/api/users', $spec['paths']);
        self::assertArrayHasKey('/api/error', $spec['paths']);
    }

    public function testApiIndexEndpoint(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        $path = $spec['paths']['/api']['get'];
        self::assertSame('API index', $path['summary']);
        self::assertContains('General', $path['tags']);
        self::assertArrayHasKey('200', $path['responses']);
    }

    public function testUsersEndpoint(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        $path = $spec['paths']['/api/users']['get'];
        self::assertSame('List users', $path['summary']);
        self::assertContains('Users', $path['tags']);
        self::assertArrayHasKey('200', $path['responses']);
    }

    public function testErrorEndpoint(): void
    {
        $openapi = Generator::scan([dirname(__DIR__) . '/app']);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        $path = $spec['paths']['/api/error']['get'];
        self::assertSame('Trigger a demo exception', $path['summary']);
        self::assertArrayHasKey('500', $path['responses']);
    }
}
