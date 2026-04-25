<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\Inspector\InspectConfigTool;
use PHPUnit\Framework\TestCase;

final class InspectConfigToolTest extends TestCase
{
    public function testGetName(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectConfigTool($client);

        $this->assertSame('inspect_config', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectConfigTool($client);

        $this->assertNotEmpty($tool->getDescription());
    }

    public function testGetInputSchema(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $tool = new InspectConfigTool($client);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('action', $schema['properties']);
        $this->assertArrayHasKey('filter', $schema['properties']);
    }

    public function testFetchParamsSuccess(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/params', [])
            ->willReturn([
                'success' => true,
                'data' => ['app.name' => 'MyApp', 'app.debug' => true],
                'error' => null,
            ]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Application Parameters', $text);
        $this->assertStringContainsString('MyApp', $text);
    }

    public function testFetchConfigWithGroup(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/config', ['group' => 'services'])
            ->willReturn([
                'success' => true,
                'data' => ['App\\Service\\UserService' => ['class' => 'App\\Service\\UserService']],
                'error' => null,
            ]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute(['action' => 'config', 'group' => 'services']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Configuration', $text);
        $this->assertStringContainsString('services', $text);
        $this->assertStringContainsString('UserService', $text);
    }

    public function testFetchEventsSuccess(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->with('/events', [])
            ->willReturn([
                'success' => true,
                'data' => ['kernel.request' => ['App\\Listener\\RequestListener']],
                'error' => null,
            ]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute(['action' => 'events']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Event Listeners', $text);
        $this->assertStringContainsString('kernel.request', $text);
    }

    public function testConnectionError(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => false,
                'data' => null,
                'error' => 'Failed to connect to http://localhost:8080/inspect/api/params',
            ]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute([]);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Failed to connect', $result['content'][0]['text']);
    }

    public function testEmptyData(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client->method('get')->willReturn(['success' => true, 'data' => [], 'error' => null]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute([]);

        $this->assertStringContainsString('No params data available', $result['content'][0]['text']);
    }

    public function testFilterResults(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->method('get')
            ->willReturn([
                'success' => true,
                'data' => ['app.name' => 'MyApp', 'app.debug' => true, 'db.host' => 'localhost'],
                'error' => null,
            ]);

        $tool = new InspectConfigTool($client);
        $result = $tool->execute(['filter' => 'app']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('app.name', $text);
        $this->assertStringContainsString('filtered', $text);
    }

    public function testServiceParameter(): void
    {
        $client = $this->createMock(InspectorInterface::class);
        $client
            ->expects($this->once())
            ->method('get')
            ->with('/params', ['service' => 'api-service'])
            ->willReturn(['success' => true, 'data' => ['key' => 'value'], 'error' => null]);

        $tool = new InspectConfigTool($client);
        $tool->execute(['service' => 'api-service']);
    }
}
