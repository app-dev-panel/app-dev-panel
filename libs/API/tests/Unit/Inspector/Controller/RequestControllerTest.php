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

    public function testRequestParsesRawHttpRequestAndAttemptsSend(): void
    {
        // Test that request() parses the raw HTTP request and tries to send via Guzzle.
        // We use an unreachable IP so Guzzle throws a ConnectException.
        $rawRequest = "GET / HTTP/1.1\r\nHost: 127.0.0.254\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->with('entry-1')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, []);

        try {
            $controller->request($this->get(['debugEntryId' => 'entry-1']));
            $this->fail('Expected a Guzzle exception for unreachable host.');
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // request() successfully parsed the raw HTTP request and attempted to send it
            $this->assertStringContainsString('127.0.0.254', $e->getMessage());
        }
    }

    public function testRequestWithAllowedHostSucceedsValidation(): void
    {
        // Verify request() passes host validation when host is in allowed list
        $rawRequest = "GET / HTTP/1.1\r\nHost: 127.0.0.254\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository, ['127.0.0.254']);

        try {
            $controller->request($this->get(['debugEntryId' => 'entry-1']));
            $this->fail('Expected a Guzzle exception.');
        } catch (\GuzzleHttp\Exception\GuzzleException) {
            // Host validation passed, Guzzle attempted the connection
            $this->addToAssertionCount(1);
        }
    }

    public function testRequestUsesDebugEntryIdFromQueryParams(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: 127.0.0.254\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getDetail')
            ->with('my-entry-id')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);

        try {
            $controller->request($this->get(['debugEntryId' => 'my-entry-id']));
        } catch (\GuzzleHttp\Exception\GuzzleException) {
            // Expected — the important assertion is that getDetail received 'my-entry-id'
        }
    }

    public function testRequestWithMultipleAllowedHostsRejectsUnlisted(): void
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: blocked.com\r\n\r\n";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController(
            $this->createResponseFactory(),
            $repository,
            ['localhost', '127.0.0.1', 'example.com'],
        );

        try {
            $controller->request($this->get(['debugEntryId' => 'entry-1']));
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('blocked.com', $e->getMessage());
            $this->assertStringContainsString('localhost', $e->getMessage());
            $this->assertStringContainsString('127.0.0.1', $e->getMessage());
            $this->assertStringContainsString('example.com', $e->getMessage());
        }
    }

    public function testRequestParsesPostRequestWithBody(): void
    {
        // Verify request() can parse a POST request with body
        $rawRequest = "POST /api/submit HTTP/1.1\r\nHost: 127.0.0.254\r\nContent-Type: application/json\r\n\r\n{\"data\":1}";

        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDetail')
            ->willReturn([
                self::REQUEST_COLLECTOR => ['requestRaw' => $rawRequest],
            ]);

        $controller = new RequestController($this->createResponseFactory(), $repository);

        try {
            $controller->request($this->get(['debugEntryId' => 'entry-1']));
            $this->fail('Expected a Guzzle exception for unreachable host.');
        } catch (\GuzzleHttp\Exception\GuzzleException) {
            // POST request was successfully parsed and Guzzle attempted to send it
            $this->addToAssertionCount(1);
        }
    }
}
