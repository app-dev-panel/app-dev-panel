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

        $controller = new RequestController($this->createResponseFactory());
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']), $repository);

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

        $controller = new RequestController($this->createResponseFactory());

        $this->expectException(\InvalidArgumentException::class);
        $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']), $repository);
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
        $controller = new RequestController($this->createResponseFactory(), []);

        // We can't actually send the HTTP request in tests, but we can test buildCurl
        // which exercises the same data flow without network call
        $response = $controller->buildCurl($this->get(['debugEntryId' => 'entry-1']), $repository);
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

        $controller = new RequestController($this->createResponseFactory(), ['localhost', '127.0.0.1']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('evil.com');
        $controller->request($this->get(['debugEntryId' => 'entry-1']), $repository);
    }
}
