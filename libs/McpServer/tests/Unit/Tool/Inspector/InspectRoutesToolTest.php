<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\Inspector\InspectRoutesTool;
use PHPUnit\Framework\TestCase;

final class InspectRoutesToolTest extends TestCase
{
    public function testGetName(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectRoutesTool($client);

        $this->assertSame('inspect_routes', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectRoutesTool($client);

        $this->assertNotEmpty($tool->getDescription());
    }

    public function testGetInputSchema(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectRoutesTool($client);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('action', $schema['properties']);
        $this->assertArrayHasKey('path', $schema['properties']);
        $this->assertArrayHasKey('filter', $schema['properties']);
    }

    public function testListRoutesSuccess(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/routes', [])
            ->willReturn([
                'success' => true,
                'data' => [
                    ['name' => 'user.list', 'pattern' => '/api/users', 'methods' => ['GET']],
                    ['name' => 'user.create', 'pattern' => '/api/users', 'methods' => ['POST']],
                ],
                'error' => null,
            ]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Application Routes (2)', $text);
        $this->assertStringContainsString('/api/users', $text);
        $this->assertStringContainsString('GET', $text);
        $this->assertStringContainsString('POST', $text);
    }

    public function testListRoutesWithFilter(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => [
                    ['name' => 'user.list', 'pattern' => '/api/users', 'methods' => ['GET']],
                    ['name' => 'order.list', 'pattern' => '/api/orders', 'methods' => ['GET']],
                ],
                'error' => null,
            ]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute(['filter' => 'user']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Application Routes (1)', $text);
        $this->assertStringContainsString('/api/users', $text);
        $this->assertStringNotContainsString('/api/orders', $text);
    }

    public function testListRoutesEmpty(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => true, 'data' => [], 'error' => null]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute([]);

        $this->assertStringContainsString('No routes found', $result['content'][0]['text']);
    }

    public function testCheckRouteMatch(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/route/check', ['route' => 'GET /api/users'])
            ->willReturn([
                'success' => true,
                'data' => ['result' => true, 'action' => 'App\\Controller\\UserController::list'],
                'error' => null,
            ]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute(['action' => 'check', 'path' => 'GET /api/users']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Match found', $text);
        $this->assertStringContainsString('UserController', $text);
    }

    public function testCheckRouteNoMatch(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => ['result' => false],
                'error' => null,
            ]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute(['action' => 'check', 'path' => '/nonexistent']);

        $this->assertStringContainsString('No match', $result['content'][0]['text']);
    }

    public function testCheckRouteMissingPath(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectRoutesTool($client);

        $result = $tool->execute(['action' => 'check']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('path', $result['content'][0]['text']);
    }

    public function testConnectionError(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => false, 'data' => null, 'error' => 'Connection refused']);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute([]);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Connection refused', $result['content'][0]['text']);
    }

    public function testFilterNoMatches(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => [
                    ['name' => 'user.list', 'pattern' => '/api/users', 'methods' => ['GET']],
                ],
                'error' => null,
            ]);

        $tool = new InspectRoutesTool($client);
        $result = $tool->execute(['filter' => 'nonexistent']);

        $this->assertStringContainsString('No routes matching', $result['content'][0]['text']);
    }
}
