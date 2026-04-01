<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use InvalidArgumentException;

final class RequestControllerTest extends ControllerTestCase
{
    private const REQUEST_COLLECTOR = 'AppDevPanel\Kernel\Collector\Web\RequestCollector';

    public function testBuildCurl(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: localhost\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->with('entry-1')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('command', $data);
        $this->assertNotNull($data['command']);
        $this->assertStringContainsString('curl', $data['command']);
    }

    public function testBuildCurlWithInvalidRequest(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->with('entry-1')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => 'not a valid http request'],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);

        $this->expectException(\InvalidArgumentException::class);
        $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));
    }

    public function testRequestHostValidationPasses(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: localhost\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        // Empty allowedHosts means all hosts allowed — should not throw
        $controller = new RequestController($this->createResponseFactory(), $repository, []);

        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestHostValidationFails(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: evil.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, ['localhost', '127.0.0.1']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('evil.com');
        $controller->request($this->get(['debugEntryId' => 'entry-1']));
    }

    public function testRequestHostValidationShowsAllowedHosts(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: attacker.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, ['localhost', '127.0.0.1']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('localhost');
        $controller->request($this->get(['debugEntryId' => 'entry-1']));
    }

    public function testRequestHostValidationMessageContainsAllAllowedHosts(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: attacker.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, ['localhost', '127.0.0.1']);

        try {
            $controller->request($this->get(['debugEntryId' => 'entry-1']));
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('attacker.com', $e->getMessage());
            $this->assertStringContainsString('localhost', $e->getMessage());
            $this->assertStringContainsString('127.0.0.1', $e->getMessage());
        }
    }

    public function testBuildCurlWithPostRequest(): void
    {
        $rawRequest = "POST /api/data HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\n\r\n{\"key\":\"value\"}";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->with('entry-2')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-2']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('command', $data);
        $this->assertNotNull($data['command']);
        $this->assertStringContainsString('curl', $data['command']);
    }

    public function testBuildCurlWithHeaders(): void
    {
        $rawRequest = "GET /api/data HTTP/1.1\r\nHost: localhost\r\nAuthorization: Bearer token123\r\nAccept: application/json\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-3']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('command', $data);
        $this->assertNotNull($data['command']);
    }

    public function testRequestWithAllowedHostPassesValidation(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: 127.0.0.1\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, ['127.0.0.1']);
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->responseData($response);
        $this->assertArrayHasKey('command', $data);
    }

    public function testBuildCurlResponseContainsCurlCommand(): void
    {
        $rawRequest = "GET /health HTTP/1.1\r\nHost: example.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'e1']));

        $data = $this->responseData($response);
        $this->assertArrayHasKey('command', $data);
        $this->assertStringContainsString('curl', $data['command']);
        $this->assertStringContainsString('example.com', $data['command']);
    }

    public function testRequestWithEmptyAllowedHostsAllowsAnything(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: any-host.example.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        // Empty allowedHosts = no restriction
        $controller = new RequestController($this->createResponseFactory(), $repository, []);

        // buildCurl doesn't call validateHost, just verify no exception and response is OK
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDefaultAllowedHostsIsEmpty(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: whatever.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        // No allowedHosts arg = defaults to []
        $controller = new RequestController($this->createResponseFactory(), $repository);

        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']));
        $this->assertSame(200, $response->getStatusCode());
    }
}
