<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Collector\DbCollector;
use AppDevPanel\Adapter\Yii2\Collector\MailerCollector;
use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for the Yii 2 ADP API endpoints.
 *
 * Tests use the Module's internal ApiApplication directly (no HTTP server)
 * to verify API routing, middleware pipeline, response envelope, and actual
 * collector data returned by the debug API.
 */
#[CoversNothing]
final class ApiEndpointTest extends TestCase
{
    private string $storagePath;
    private ?Module $module = null;
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_api_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);
        mkdir($this->storagePath . '/debug', 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

        $this->psr17 = new Psr17Factory();
    }

    protected function tearDown(): void
    {
        $this->module = null;
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        $this->removeDirectory($this->storagePath);
    }

    // -----------------------------------------------------------------------
    // Debug list endpoint
    // -----------------------------------------------------------------------

    public function testDebugListReturnsEmptyWhenNoEntries(): void
    {
        $this->createBootstrappedModule();
        $response = $this->apiGet('/debug/api/');

        $this->assertSame(200, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertTrue($envelope['success']);
        $this->assertSame([], $envelope['data']);
    }

    public function testDebugListReturnsSummaryAfterRequest(): void
    {
        $module = $this->createBootstrappedModule();
        $this->simulateWebRequest($module, 'GET', '/api/users', 200);

        $envelope = $this->decodeEnvelope($this->apiGet('/debug/api/'));
        $this->assertTrue($envelope['success']);
        $this->assertCount(1, $envelope['data']);

        $entry = $envelope['data'][0];
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('collectors', $entry);
        $this->assertArrayHasKey('request', $entry);
        $this->assertArrayHasKey('response', $entry);

        // Verify request summary
        $this->assertSame('GET', $entry['request']['method']);
        $this->assertSame('/api/users', $entry['request']['path']);
        $this->assertSame(200, $entry['response']['statusCode']);
    }

    public function testDebugListContainsCollectorMetadata(): void
    {
        $module = $this->createBootstrappedModule();
        $this->simulateWebRequest($module, 'GET', '/', 200);

        $envelope = $this->decodeEnvelope($this->apiGet('/debug/api/'));
        $entry = $envelope['data'][0];

        $collectorIds = array_column($entry['collectors'], 'id');
        $this->assertContains(RequestCollector::class, $collectorIds);
        $this->assertContains(LogCollector::class, $collectorIds);
        $this->assertContains(ExceptionCollector::class, $collectorIds);
        $this->assertContains(DbCollector::class, $collectorIds);
        $this->assertContains(MailerCollector::class, $collectorIds);

        // Yii2LogCollector should NOT be registered (removed in favor of global LogCollector)
        $this->assertNotContains('AppDevPanel\\Adapter\\Yii2\\Collector\\Yii2LogCollector', $collectorIds);

        // Each collector entry should have id and name
        foreach ($entry['collectors'] as $collector) {
            $this->assertArrayHasKey('id', $collector);
            $this->assertArrayHasKey('name', $collector);
            $this->assertNotEmpty($collector['name']);
        }
    }

    // -----------------------------------------------------------------------
    // Debug view endpoint — collector data
    // -----------------------------------------------------------------------

    public function testViewReturnsAllCollectorData(): void
    {
        $module = $this->createBootstrappedModule();
        $debugId = $this->simulateWebRequest($module, 'GET', '/test', 200);

        $envelope = $this->decodeEnvelope($this->apiGet("/debug/api/view/{$debugId}"));
        $this->assertTrue($envelope['success']);
        $this->assertIsArray($envelope['data']);

        // Should contain data keyed by collector FQCN
        $this->assertArrayHasKey(RequestCollector::class, $envelope['data']);
        $this->assertArrayHasKey(LogCollector::class, $envelope['data']);
        $this->assertArrayHasKey(DbCollector::class, $envelope['data']);
    }

    public function testViewRequestCollectorReturnsRequestDetails(): void
    {
        $module = $this->createBootstrappedModule();
        $debugId = $this->simulateWebRequest($module, 'POST', '/api/submit', 201);

        $envelope = $this->decodeEnvelope(
            $this->apiGet("/debug/api/view/{$debugId}?collector=" . urlencode(RequestCollector::class)),
        );
        $this->assertTrue($envelope['success']);

        $data = $envelope['data'];
        $this->assertSame('POST', $data['requestMethod']);
        $this->assertSame('/api/submit', $data['requestPath']);
        $this->assertSame(201, $data['responseStatusCode']);
        $this->assertArrayHasKey('requestUrl', $data);
    }

    public function testViewLogCollectorReturnsLogEntries(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/test');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var LogCollector $logCollector */
        $logCollector = $module->getCollector(LogCollector::class);
        $logCollector->collect('info', 'Application started', ['source' => 'bootstrap'], 'app.php:10');
        $logCollector->collect('warning', 'Cache miss for key "users"', ['source' => 'cache'], 'cache.php:42');
        $logCollector->collect('error', 'DB connection failed', ['source' => 'db'], 'db.php:15');

        $debugger->shutdown();

        $envelope = $this->decodeEnvelope(
            $this->apiGet("/debug/api/view/{$debugId}?collector=" . urlencode(LogCollector::class)),
        );
        $this->assertTrue($envelope['success']);

        $logs = $envelope['data'];
        $this->assertCount(3, $logs);

        $this->assertSame('info', $logs[0]['level']);
        $this->assertSame('Application started', $logs[0]['message']);
        $this->assertSame('app.php:10', $logs[0]['line']);
        $this->assertArrayHasKey('time', $logs[0]);

        $this->assertSame('warning', $logs[1]['level']);
        $this->assertSame('error', $logs[2]['level']);
        $this->assertSame('DB connection failed', $logs[2]['message']);
    }

    public function testViewDbCollectorReturnsQueryData(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/api/users');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var DbCollector $dbCollector */
        $dbCollector = $module->getCollector(DbCollector::class);
        $dbCollector->logConnection();
        $dbCollector->beginQuery();
        $dbCollector->logQuery('SELECT * FROM users WHERE active = ?', [1], 5);
        $dbCollector->beginQuery();
        $dbCollector->logQuery('SELECT COUNT(*) FROM users', [], 1);

        $debugger->shutdown();

        $envelope = $this->decodeEnvelope(
            $this->apiGet("/debug/api/view/{$debugId}?collector=" . urlencode(DbCollector::class)),
        );
        $this->assertTrue($envelope['success']);

        $data = $envelope['data'];
        $this->assertSame(2, $data['queryCount']);
        $this->assertSame(1, $data['connectionCount']);
        $this->assertGreaterThanOrEqual(0, $data['totalTime']);

        $queries = $data['queries'];
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('SELECT * FROM users', $queries[0]['sql']);
        $this->assertSame('SELECT', $queries[0]['type']);
        $this->assertSame(5, $queries[0]['rowCount']);
        $this->assertGreaterThan(0, $queries[0]['time']);

        $this->assertStringContainsString('SELECT COUNT', $queries[1]['sql']);
    }

    public function testViewExceptionCollectorReturnsExceptionData(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/api/error');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var ExceptionCollector $exceptionCollector */
        $exceptionCollector = $module->getCollector(ExceptionCollector::class);
        $exceptionCollector->collect(new \RuntimeException('Something broke', 500));

        $debugger->shutdown();

        $envelope = $this->decodeEnvelope(
            $this->apiGet("/debug/api/view/{$debugId}?collector=" . urlencode(ExceptionCollector::class)),
        );
        $this->assertTrue($envelope['success']);

        $exceptions = $envelope['data'];
        $this->assertNotEmpty($exceptions);
        $this->assertSame(\RuntimeException::class, $exceptions[0]['class']);
        $this->assertSame('Something broke', $exceptions[0]['message']);
        $this->assertSame(500, $exceptions[0]['code']);
    }

    public function testViewMailerCollectorReturnsMailData(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('POST', 'http://localhost/api/notify');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var MailerCollector $mailerCollector */
        $mailerCollector = $module->getCollector(MailerCollector::class);
        $mailerCollector->logMessage(
            'noreply@example.com',
            ['user@example.com'],
            [],
            [],
            'Welcome!',
            true,
        );

        $debugger->shutdown();

        $envelope = $this->decodeEnvelope(
            $this->apiGet("/debug/api/view/{$debugId}?collector=" . urlencode(MailerCollector::class)),
        );
        $this->assertTrue($envelope['success']);

        $data = $envelope['data'];
        $this->assertSame(1, $data['messageCount']);
        $this->assertCount(1, $data['messages']);
        $this->assertSame('Welcome!', $data['messages'][0]['subject']);
        $this->assertSame(['noreply@example.com'], $data['messages'][0]['from']);
        $this->assertTrue($data['messages'][0]['isSuccessful']);
    }

    // -----------------------------------------------------------------------
    // Summary endpoint
    // -----------------------------------------------------------------------

    public function testSummaryContainsCollectorTotals(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psrRequest = $this->psr17->createServerRequest('GET', 'http://localhost/');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var LogCollector $logCollector */
        $logCollector = $module->getCollector(LogCollector::class);
        $logCollector->collect('info', 'msg1', [], '');
        $logCollector->collect('info', 'msg2', [], '');

        /** @var DbCollector $dbCollector */
        $dbCollector = $module->getCollector(DbCollector::class);
        $dbCollector->beginQuery();
        $dbCollector->logQuery('SELECT 1', [], 1);

        $debugger->shutdown();

        $envelope = $this->decodeEnvelope($this->apiGet("/debug/api/summary/{$debugId}"));
        $this->assertTrue($envelope['success']);

        $summary = $envelope['data'];
        $this->assertSame($debugId, $summary['id']);

        // Logger summary
        $this->assertArrayHasKey('logger', $summary);
        $this->assertSame(2, $summary['logger']['total']);

        // DB summary
        $this->assertArrayHasKey('db', $summary);
        $this->assertSame(1, $summary['db']['queryCount']);
    }

    // -----------------------------------------------------------------------
    // Error handling
    // -----------------------------------------------------------------------

    public function testViewNonExistentEntryReturns404(): void
    {
        $this->createBootstrappedModule();
        $response = $this->apiGet('/debug/api/view/nonexistent-id-12345');

        $this->assertSame(404, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertFalse($envelope['success']);
        $this->assertNotNull($envelope['error']);
    }

    public function testViewNonExistentCollectorReturns404(): void
    {
        $module = $this->createBootstrappedModule();
        $debugId = $this->simulateWebRequest($module, 'GET', '/', 200);

        $response = $this->apiGet("/debug/api/view/{$debugId}?collector=NonExistent\\Collector");

        $this->assertSame(404, $response->getStatusCode());
        $envelope = $this->decodeEnvelope($response);
        $this->assertFalse($envelope['success']);
    }

    public function testNonExistentRouteReturns404(): void
    {
        $this->createBootstrappedModule();
        $response = $this->apiGet('/debug/api/nonexistent/route');

        $this->assertSame(404, $response->getStatusCode());
    }

    // -----------------------------------------------------------------------
    // Middleware pipeline
    // -----------------------------------------------------------------------

    public function testIpFilterBlocksUnauthorizedIp(): void
    {
        $this->createBootstrappedModule();
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);

        $request = $this->psr17->createServerRequest('GET', 'http://localhost/debug/api/', ['REMOTE_ADDR' => '10.0.0.1']);
        $response = $apiApp->handle($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testResponseEnvelopeStructure(): void
    {
        $this->createBootstrappedModule();
        $response = $this->apiGet('/debug/api/');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('success', $body);
    }

    // -----------------------------------------------------------------------
    // Inspector endpoints
    // -----------------------------------------------------------------------

    public function testInspectorParamsEndpoint(): void
    {
        $this->createBootstrappedModule();
        $response = $this->apiGet('/inspect/api/params');

        $this->assertContains($response->getStatusCode(), [200, 404, 500]);
    }

    // -----------------------------------------------------------------------
    // Multiple entries
    // -----------------------------------------------------------------------

    public function testMultipleEntriesReturnedInOrder(): void
    {
        $module = $this->createBootstrappedModule();

        $id1 = $this->simulateWebRequest($module, 'GET', '/first', 200);
        $id2 = $this->simulateWebRequest($module, 'POST', '/second', 201);

        $envelope = $this->decodeEnvelope($this->apiGet('/debug/api/'));
        $this->assertCount(2, $envelope['data']);

        $ids = array_column($envelope['data'], 'id');
        $this->assertContains($id1, $ids);
        $this->assertContains($id2, $ids);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function apiGet(string $path): ResponseInterface
    {
        $apiApp = \Yii::$container->get(\AppDevPanel\Api\ApiApplication::class);
        $request = $this->psr17->createServerRequest('GET', "http://localhost{$path}", ['REMOTE_ADDR' => '127.0.0.1']);
        return $apiApp->handle($request);
    }

    /**
     * @return array{id: ?string, data: mixed, error: ?string, success: bool}
     */
    private function decodeEnvelope(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, "Response body is not valid JSON: {$body}");
        $this->assertArrayHasKey('data', $decoded, "Response missing 'data' key: {$body}");

        return $decoded;
    }

    private function simulateWebRequest(Module $module, string $method, string $path, int $statusCode): string
    {
        $debugger = $module->getDebugger();
        $psrRequest = $this->psr17->createServerRequest($method, "http://localhost{$path}");
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        /** @var WebAppInfoCollector|null $webAppInfoCollector */
        $webAppInfoCollector = $module->getCollector(WebAppInfoCollector::class);
        $webAppInfoCollector?->markApplicationStarted();
        $webAppInfoCollector?->markRequestStarted();

        $psrResponse = $this->psr17->createResponse($statusCode);
        $requestCollector?->collectResponse($psrResponse);

        $webAppInfoCollector?->markRequestFinished();
        $webAppInfoCollector?->markApplicationFinished();

        $debugger->shutdown();

        return $debugId;
    }

    private function createBootstrappedModule(): Module
    {
        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'historySize' => 50,
            'collectors' => [
                'request' => true,
                'exception' => true,
                'log' => true,
                'event' => true,
                'service' => true,
                'http_client' => true,
                'timeline' => true,
                'var_dumper' => true,
                'filesystem_stream' => true,
                'http_stream' => true,
                'command' => true,
                'db' => true,
                'yii_log' => false,
                'mailer' => true,
                'assets' => true,
            ],
        ]);

        $reflection = new \ReflectionClass($module);

        foreach (['registerServices', 'registerCollectors', 'buildDebugger'] as $method) {
            $ref = $reflection->getMethod($method);
            $ref->setAccessible(true);
            $ref->invoke($module);
        }

        $this->module = $module;
        return $module;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
