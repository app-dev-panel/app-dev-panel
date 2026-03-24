<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Http;

use AppDevPanel\Api\Http\JsonResponseFactory;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

final class JsonResponseFactoryTest extends TestCase
{
    public function testCreateJsonResponse(): void
    {
        $factory = new JsonResponseFactory(new HttpFactory(), new HttpFactory());

        $response = $factory->createJsonResponse(['key' => 'value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['key' => 'value'], $body);
    }

    public function testCreateJsonResponseWithCustomStatus(): void
    {
        $factory = new JsonResponseFactory(new HttpFactory(), new HttpFactory());

        $response = $factory->createJsonResponse(['error' => 'not found'], 404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateJsonResponsePreservesUnicode(): void
    {
        $factory = new JsonResponseFactory(new HttpFactory(), new HttpFactory());

        $response = $factory->createJsonResponse(['name' => 'Тест']);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('Тест', $body);
    }

    public function testCreateJsonResponsePreservesSlashes(): void
    {
        $factory = new JsonResponseFactory(new HttpFactory(), new HttpFactory());

        $response = $factory->createJsonResponse(['url' => 'https://example.com/path']);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('https://example.com/path', $body);
        $this->assertStringNotContainsString('\\/', $body);
    }
}
