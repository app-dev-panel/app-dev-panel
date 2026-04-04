<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;

/**
 * Creates real OTel spans via the Tracer API.
 * The SpanProcessorInterfaceProxy intercepts onEnd() and feeds OpenTelemetryCollector.
 */
final class OpenTelemetryAction
{
    public function __construct(
        private readonly TracerProviderInterface $tracerProvider,
    ) {}

    public function __invoke(): JsonResponse
    {
        $tracer = $this->tracerProvider->getTracer('adp-fixture', '1.0.0');

        // Root span: POST /api/orders
        $rootSpan = $tracer->spanBuilder('POST /api/orders')->setSpanKind(SpanKind::KIND_SERVER)->startSpan();
        $rootScope = $rootSpan->activate();

        $rootSpan->setAttribute('http.method', 'POST');
        $rootSpan->setAttribute('http.url', '/api/orders');
        $rootSpan->setAttribute('http.status_code', 201);

        // Child span: validate-order
        $validateSpan = $tracer->spanBuilder('validate-order')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        $validateSpan->setAttribute('order.items_count', 3);
        $validateSpan->setStatus(StatusCode::STATUS_OK);
        $validateSpan->end();

        // Child span: INSERT orders (database)
        $dbSpan = $tracer->spanBuilder('INSERT orders')->setSpanKind(SpanKind::KIND_CLIENT)->startSpan();
        $dbSpan->setAttribute('db.system', 'postgresql');
        $dbSpan->setAttribute('db.statement', 'INSERT INTO orders (user_id, total) VALUES ($1, $2)');
        $dbSpan->setStatus(StatusCode::STATUS_OK);
        $dbSpan->end();

        // Child span: send-notification (fails)
        $notifySpan = $tracer->spanBuilder('send-notification')->setSpanKind(SpanKind::KIND_CLIENT)->startSpan();
        $notifySpan->setAttribute('messaging.system', 'smtp');
        $notifySpan->setAttribute('messaging.destination', 'orders@example.com');
        $notifySpan->addEvent('exception', [
            'exception.type' => 'SmtpException',
            'exception.message' => 'Connection timed out after 5s',
        ]);
        $notifySpan->setStatus(StatusCode::STATUS_ERROR, 'SMTP connection timeout');
        $notifySpan->end();

        // End root span
        $rootSpan->setStatus(StatusCode::STATUS_OK);
        $rootSpan->end();
        $rootScope->detach();

        return new JsonResponse(['fixture' => 'opentelemetry', 'status' => 'ok']);
    }
}
