<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\InspectController;
use InvalidArgumentException;
use Yiisoft\Config\ConfigInterface;

final class InspectControllerTest extends ControllerTestCase
{
    private function createController(array $params = []): InspectController
    {
        return new InspectController($this->createResponseFactory(), $params);
    }

    public function testConfig(): void
    {
        $configData = ['key1' => 'value1', 'key2' => 'value2'];

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())->method('get')->with('di')->willReturn($configData);

        $container = $this->container([ConfigInterface::class => $config]);

        $controller = $this->createController();
        $response = $controller->config($container, $this->get());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfigWithGroup(): void
    {
        $configData = ['param' => 'value'];

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())->method('get')->with('params')->willReturn($configData);

        $container = $this->container([ConfigInterface::class => $config]);

        $controller = $this->createController();
        $response = $controller->config($container, $this->get(['group' => 'params']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testParams(): void
    {
        $params = ['locale' => ['en'], 'debug' => true];
        $controller = $this->createController($params);
        $response = $controller->params();

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(true, $data['debug']);
    }

    public function testParamsSorted(): void
    {
        $params = ['z_param' => 1, 'a_param' => 2];
        $controller = $this->createController($params);
        $response = $controller->params();

        $data = $this->responseData($response);
        $keys = array_keys($data);
        $this->assertSame('a_param', $keys[0]);
        $this->assertSame('z_param', $keys[1]);
    }

    public function testClasses(): void
    {
        $controller = $this->createController();
        $response = $controller->classes();

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsArray($data);
        // Should be sorted
        if (count($data) > 1) {
            $this->assertLessThanOrEqual(0, strcmp($data[0], $data[1]));
        }
    }

    public function testObjectSuccess(): void
    {
        $service = new \AppDevPanel\Api\Inspector\CommandResponse('ok', '', []);

        $container = $this->container([\AppDevPanel\Api\Inspector\CommandResponse::class => $service]);

        $controller = $this->createController();
        $response = $controller->object($container, $this->get([
            'classname' => \AppDevPanel\Api\Inspector\CommandResponse::class,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('object', $data);
        $this->assertArrayHasKey('path', $data);
    }

    public function testObjectRequiresClassname(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('classname');
        $controller->object($container, $this->get());
    }

    public function testObjectEmptyClassname(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->object($container, $this->get(['classname' => '']));
    }

    public function testObjectClassNotExists(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $controller->object($container, $this->get(['classname' => 'NonExistent\\FakeClass']));
    }

    public function testObjectInternalClass(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('internal classes');
        $controller->object($container, $this->get(['classname' => \DateTime::class]));
    }

    public function testObjectThrowableClass(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        // NotFoundException is a non-internal Throwable from this project
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceptions');
        $controller->object($container, $this->get([
            'classname' => \AppDevPanel\Api\Debug\Exception\NotFoundException::class,
        ]));
    }

    public function testObjectNotInContainer(): void
    {
        $container = $this->container();

        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');
        $controller->object($container, $this->get([
            'classname' => \AppDevPanel\Api\Inspector\CommandResponse::class,
        ]));
    }

    public function testPhpinfo(): void
    {
        $controller = $this->createController();
        $response = $controller->phpinfo();

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsString($data);
        $this->assertStringContainsString('PHP', $data);
    }

    public function testEventListeners(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->willReturnCallback(static fn(string $group) => match ($group) {
                'events' => ['App\\Event' => [['handler']]],
                'events-web' => ['App\\WebEvent' => [['webHandler']]],
            });

        $container = $this->container([ConfigInterface::class => $config]);

        $controller = $this->createController();
        $response = $controller->eventListeners($container);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('common', $data);
        $this->assertArrayHasKey('console', $data);
        $this->assertArrayHasKey('web', $data);
        $this->assertSame([], $data['console']);
    }
}
