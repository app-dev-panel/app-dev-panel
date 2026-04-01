<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\OpcacheController;

final class OpcacheControllerTest extends ControllerTestCase
{
    public function testIndexWhenOpcacheAvailable(): void
    {
        $controller = new OpcacheController($this->createResponseFactory());
        $response = $controller->index($this->get());

        // opcache may or may not be available in the test environment
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    public function testIndexResponseStructure(): void
    {
        $controller = new OpcacheController($this->createResponseFactory());
        $response = $controller->index($this->get());

        $data = $this->responseData($response);

        if ($response->getStatusCode() === 422) {
            // OPcache not available
            $this->assertArrayHasKey('message', $data);
            $this->assertStringContainsString('OPcache', $data['message']);
        } else {
            // OPcache available
            $this->assertArrayHasKey('status', $data);
            $this->assertArrayHasKey('configuration', $data);
        }
    }

    public function testIndexReturns422MessageWhenNotAvailable(): void
    {
        // If opcache is not loaded, we should get a 422 with a message
        if (\function_exists('opcache_get_status') && \opcache_get_status(true) !== false) {
            $this->markTestSkipped('OPcache is enabled in this environment.');
        }

        $controller = new OpcacheController($this->createResponseFactory());
        $response = $controller->index($this->get());

        $this->assertSame(422, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame('OPcache is not installed or configured', $data['message']);
    }

    public function testIndexContentType(): void
    {
        $controller = new OpcacheController($this->createResponseFactory());
        $response = $controller->index($this->get());

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }
}
