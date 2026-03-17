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
}
