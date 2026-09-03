<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\OpcacheController;

final class OpcacheControllerTest extends ControllerTestCase
{
    private const array STATUS = [
        'opcache_enabled' => true,
        'memory_usage' => ['used_memory' => 1024, 'free_memory' => 4096],
    ];

    private const array CONFIGURATION = [
        'directives' => ['opcache.enable' => true],
        'version' => ['version' => '8.4.0'],
    ];

    public function testIndexReturnsStatusAndConfigurationWhenAvailable(): void
    {
        $configurationCalls = 0;
        $controller = new OpcacheController(
            $this->createResponseFactory(),
            static fn(): array => self::STATUS,
            static function () use (&$configurationCalls): array {
                $configurationCalls++;
                return self::CONFIGURATION;
            },
        );

        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['status' => self::STATUS, 'configuration' => self::CONFIGURATION],
            $this->responseData($response),
        );
        $this->assertSame(1, $configurationCalls);
    }

    public function testIndexReturns422WhenStatusProviderReportsUnavailable(): void
    {
        $configurationCalls = 0;
        $controller = new OpcacheController(
            $this->createResponseFactory(),
            static fn(): false => false,
            static function () use (&$configurationCalls): array {
                $configurationCalls++;
                return self::CONFIGURATION;
            },
        );

        $response = $controller->index($this->get());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['message' => 'OPcache is not installed or configured'], $this->responseData($response));
        $this->assertSame(0, $configurationCalls, 'Configuration must not be read when OPcache is unavailable');
    }

    public function testIndexContentType(): void
    {
        $controller = new OpcacheController($this->createResponseFactory(), static fn(): false => false);

        $response = $controller->index($this->get());

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testDefaultProvidersProduceAValidResponseShape(): void
    {
        // Default providers call the real extension; the environment decides which branch is taken,
        // but the response shape must be valid either way.
        $controller = new OpcacheController($this->createResponseFactory());

        $response = $controller->index($this->get());
        $data = $this->responseData($response);

        if ($response->getStatusCode() === 422) {
            $this->assertSame(['message' => 'OPcache is not installed or configured'], $data);
            return;
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($data['status']);
        $this->assertIsArray($data['configuration']);
    }
}
