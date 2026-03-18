<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ADP Testing Scenarios — endpoints that trigger specific collector behaviors.
 * Defined centrally in libs/Testing, implemented per-playground.
 */
#[Route('/test/scenarios')]
final class TestScenariosController extends AbstractController
{
    /**
     * Scenario: logs:basic — Emit info, warning, error logs.
     */
    #[Route('/logs', name: 'test_logs', methods: ['GET'])]
    public function logs(LoggerInterface $logger): JsonResponse
    {
        $logger->info('Test log: info level message');
        $logger->warning('Test log: warning level message');
        $logger->error('Test log: error level message');

        return $this->json(['scenario' => 'logs:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: logs:context — Log with structured context.
     */
    #[Route('/logs-context', name: 'test_logs_context', methods: ['GET'])]
    public function logsContext(LoggerInterface $logger): JsonResponse
    {
        $logger->info('User action', [
            'user_id' => 42,
            'action' => 'login',
            'ip' => '127.0.0.1',
        ]);

        return $this->json(['scenario' => 'logs:context', 'status' => 'ok']);
    }

    /**
     * Scenario: events:basic — Dispatch an event.
     */
    #[Route('/events', name: 'test_events', methods: ['GET'])]
    public function events(EventDispatcherInterface $dispatcher): JsonResponse
    {
        $dispatcher->dispatch(new TestScenarioEvent('events:basic'));

        return $this->json(['scenario' => 'events:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: var-dumper:basic — Trigger a var dump.
     */
    #[Route('/dump', name: 'test_dump', methods: ['GET'])]
    public function dump(): JsonResponse
    {
        dump(['scenario' => 'var-dumper:basic', 'nested' => ['key' => 'value']]);

        return $this->json(['scenario' => 'var-dumper:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: timeline:basic — Trigger timeline entries (logs produce timeline events).
     */
    #[Route('/timeline', name: 'test_timeline', methods: ['GET'])]
    public function timeline(LoggerInterface $logger): JsonResponse
    {
        $logger->info('Timeline step 1: start');
        usleep(10_000); // 10ms
        $logger->info('Timeline step 2: processing');
        usleep(10_000);
        $logger->info('Timeline step 3: done');

        return $this->json(['scenario' => 'timeline:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: request:basic — Just a normal request (request collector captures it automatically).
     */
    #[Route('/request-info', name: 'test_request_info', methods: ['GET'])]
    public function requestInfo(): JsonResponse
    {
        return $this->json(['scenario' => 'request:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: exception:runtime — Throw a RuntimeException.
     */
    #[Route('/exception', name: 'test_exception', methods: ['GET'])]
    public function exception(): never
    {
        throw new \RuntimeException('ADP test scenario exception');
    }

    /**
     * Scenario: exception:chained — Throw an exception with a previous cause.
     */
    #[Route('/exception-chained', name: 'test_exception_chained', methods: ['GET'])]
    public function exceptionChained(): never
    {
        try {
            throw new \InvalidArgumentException('Original cause');
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Wrapper exception', 0, $e);
        }
    }

    /**
     * Scenario: multi:logs-and-events — Multiple collectors in one request.
     */
    #[Route('/multi', name: 'test_multi', methods: ['GET'])]
    public function multi(LoggerInterface $logger, EventDispatcherInterface $dispatcher): JsonResponse
    {
        $logger->info('Multi scenario: log entry 1');
        $dispatcher->dispatch(new TestScenarioEvent('multi:step'));
        $logger->info('Multi scenario: log entry 2');

        return $this->json(['scenario' => 'multi:logs-and-events', 'status' => 'ok']);
    }

    /**
     * Scenario: logs:heavy — Many log entries in one request.
     */
    #[Route('/logs-heavy', name: 'test_logs_heavy', methods: ['GET'])]
    public function logsHeavy(LoggerInterface $logger): JsonResponse
    {
        for ($i = 1; $i <= 100; $i++) {
            $logger->info(sprintf('Heavy log entry #%d', $i));
        }

        return $this->json(['scenario' => 'logs:heavy', 'status' => 'ok', 'count' => 100]);
    }

    /**
     * Scenario: http-client:basic — Make an HTTP client request.
     */
    #[Route('/http-client', name: 'test_http_client', methods: ['GET'])]
    public function httpClient(LoggerInterface $logger): JsonResponse
    {
        // Note: HTTP client collector only works if PSR-18 client is proxied.
        // In Symfony, this depends on HttpClient being wired through ADP proxy.
        // Log the attempt so at least logging is captured.
        $logger->info('HTTP client scenario: would make external request');

        return $this->json(['scenario' => 'http-client:basic', 'status' => 'ok']);
    }

    /**
     * Scenario: filesystem:basic — Trigger filesystem operations.
     */
    #[Route('/filesystem', name: 'test_filesystem', methods: ['GET'])]
    public function filesystem(): JsonResponse
    {
        $tmpFile = sys_get_temp_dir() . '/adp-test-scenario-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'ADP filesystem test scenario');
        file_get_contents($tmpFile);
        unlink($tmpFile);

        return $this->json(['scenario' => 'filesystem:basic', 'status' => 'ok']);
    }
}
