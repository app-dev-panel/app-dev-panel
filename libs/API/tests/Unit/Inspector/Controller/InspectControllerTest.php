<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\InspectController;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

final class InspectControllerTest extends ControllerTestCase
{
    private function createController(array $params = [], ?ContainerInterface $container = null): InspectController
    {
        return new InspectController($this->createResponseFactory(), $container ?? $this->container(), $params);
    }

    public function testConfig(): void
    {
        $configData = ['key1' => 'value1', 'key2' => 'value2'];

        $config = new class($configData) {
            public function __construct(
                private readonly array $data,
            ) {}

            public function get(string $group): array
            {
                return $this->data;
            }
        };

        $container = $this->container(['config' => $config]);

        $controller = $this->createController([], $container);
        $response = $controller->config($this->get());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfigWithGroup(): void
    {
        $config = new class() {
            public function get(string $group): array
            {
                return match ($group) {
                    'params' => ['param' => 'value'],
                    default => [],
                };
            }
        };

        $container = $this->container(['config' => $config]);

        $controller = $this->createController([], $container);
        $response = $controller->config($this->get(['group' => 'params']));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testParams(): void
    {
        $params = ['locale' => ['en'], 'debug' => true];
        $controller = $this->createController($params);
        $response = $controller->params($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertSame(true, $data['debug']);
    }

    public function testParamsSorted(): void
    {
        $params = ['z_param' => 1, 'a_param' => 2];
        $controller = $this->createController($params);
        $response = $controller->params($this->get());

        $data = $this->responseData($response);
        $keys = array_keys($data);
        $this->assertSame('a_param', $keys[0]);
        $this->assertSame('z_param', $keys[1]);
    }

    public function testClasses(): void
    {
        $controller = $this->createController();
        $response = $controller->classes($this->get());

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

        $controller = $this->createController([], $container);
        $response = $controller->object($this->get([
            'classname' => \AppDevPanel\Api\Inspector\CommandResponse::class,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('object', $data);
        $this->assertArrayHasKey('path', $data);
    }

    public function testObjectRequiresClassname(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('classname');
        $controller->object($this->get());
    }

    public function testObjectEmptyClassname(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $controller->object($this->get(['classname' => '']));
    }

    public function testObjectClassNotExists(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $controller->object($this->get(['classname' => 'NonExistent\\FakeClass']));
    }

    public function testObjectInternalClass(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('internal classes');
        $controller->object($this->get(['classname' => \DateTime::class]));
    }

    public function testObjectThrowableClass(): void
    {
        $controller = $this->createController();

        // NotFoundException is a non-internal Throwable from this project
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceptions');
        $controller->object($this->get([
            'classname' => \AppDevPanel\Api\Debug\Exception\NotFoundException::class,
        ]));
    }

    public function testObjectNotInContainer(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');
        $controller->object($this->get([
            'classname' => \AppDevPanel\Api\Inspector\CommandResponse::class,
        ]));
    }

    public function testPhpinfo(): void
    {
        $controller = $this->createController();
        $response = $controller->phpinfo($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertIsString($data);
        $this->assertStringContainsString('PHP', $data);
    }

    public function testEventListeners(): void
    {
        $config = new class() {
            public function get(string $group): array
            {
                return match ($group) {
                    'events' => ['App\\Event' => [['handler']]],
                    'events-web' => ['App\\WebEvent' => [['webHandler']]],
                    default => [],
                };
            }
        };

        $container = $this->container(['config' => $config]);

        $controller = $this->createController([], $container);
        $response = $controller->eventListeners($this->get());

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('common', $data);
        $this->assertArrayHasKey('console', $data);
        $this->assertArrayHasKey('web', $data);
        $this->assertSame([], $data['console']);
    }
}
