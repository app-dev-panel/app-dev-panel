<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Controller;

use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapper;
use AppDevPanel\Api\Tests\Unit\Inspector\Controller\ControllerTestCase;

final class SettingsControllerTest extends ControllerTestCase
{
    public function testSettingsWithNoPathMapping(): void
    {
        $controller = new SettingsController($this->createResponseFactory(), new NullPathMapper());
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data['pathMapping']);
    }

    public function testSettingsWithPathMapping(): void
    {
        $rules = ['/app' => '/home/user/project', '/vendor' => '/home/user/vendor'];
        $controller = new SettingsController($this->createResponseFactory(), new PathMapper($rules));
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame($rules, $data['pathMapping']);
    }

    public function testSettingsWithDefaultPathMapper(): void
    {
        $controller = new SettingsController($this->createResponseFactory());
        $response = $controller->index($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame([], $data['pathMapping']);
    }
}
