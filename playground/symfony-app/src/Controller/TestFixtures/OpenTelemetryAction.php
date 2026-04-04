<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OpenTelemetry fixture — uses the real OTel SDK Tracer API to create spans.
 * The SpanProcessorInterfaceProxy (registered in CollectorProxyCompilerPass)
 * intercepts span processing and feeds data to OpenTelemetryCollector.
 */
#[Route('/test/fixtures/opentelemetry', name: 'test_opentelemetry', methods: ['GET'])]
final readonly class OpenTelemetryAction
{
    public function __construct(
        private TracerProviderInterface $tracerProvider,
    ) {}

    public function __invoke(): JsonResponse
    {
        $tracer = $this->tracerProvider->getTracer('adp-fixture', '1.0.0');

        // Root span: HTTP request
        $rootSpan = $tracer->spanBuilder('POST /api/orders')->setSpanKind(SpanKind::KIND_SERVER)->startSpan();

        $rootScope = $rootSpan->activate();

        try {
            $rootSpan->setAttribute('http.method', 'POST');
            $rootSpan->setAttribute('http.url', '/api/orders');

            // Child span: validation
            $validateSpan = $tracer->spanBuilder('validate-order')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
            $validateSpan->setAttribute('order.items_count', 3);
            usleep(1_000); // 1ms
            $validateSpan->end();

            // Child span: database insert
            $dbSpan = $tracer->spanBuilder('INSERT orders')->setSpanKind(SpanKind::KIND_CLIENT)->startSpan();
            $dbSpan->setAttribute('db.system', 'postgresql');
            $dbSpan->setAttribute('db.statement', 'INSERT INTO orders (user_id, total) VALUES ($1, $2)');
            usleep(2_000); // 2ms
            $dbSpan->end();

            // Child span: notification (with error)
            $notifySpan = $tracer->spanBuilder('send-notification')->setSpanKind(SpanKind::KIND_CLIENT)->startSpan();
            $notifySpan->setAttribute('messaging.system', 'smtp');
            $notifySpan->setAttribute('messaging.destination', 'orders@example.com');
            $notifySpan->addEvent('exception', [
                'exception.type' => 'SmtpException',
                'exception.message' => 'Connection timed out after 5s',
            ]);
            $notifySpan->setStatus(StatusCode::STATUS_ERROR, 'SMTP connection timeout');
            usleep(1_000); // 1ms
            $notifySpan->end();

            $rootSpan->setAttribute('http.status_code', 201);
            $rootSpan->setStatus(StatusCode::STATUS_OK);
        } finally {
            $rootScope->detach();
            $rootSpan->end();
        }

        return new JsonResponse(['fixture' => 'opentelemetry', 'status' => 'ok']);
    }
}
