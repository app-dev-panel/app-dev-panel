<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\SpanRecord;
use yii\base\Action;

final class OpenTelemetryAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('adp');

        /** @var OpenTelemetryCollector|null $collector */
        $collector = $module->getCollector(OpenTelemetryCollector::class);

        if ($collector === null) {
            return ['fixture' => 'opentelemetry', 'status' => 'error', 'message' => 'OpenTelemetryCollector not found'];
        }

        $traceId = bin2hex(random_bytes(16));
        $rootSpanId = bin2hex(random_bytes(8));
        $now = microtime(true);

        $collector->collect(new SpanRecord(
            traceId: $traceId,
            spanId: $rootSpanId,
            parentSpanId: null,
            operationName: 'POST /api/orders',
            serviceName: 'order-service',
            startTime: $now,
            endTime: $now + 0.250,
            duration: 250.0,
            status: 'OK',
            kind: 'SERVER',
            attributes: ['http.method' => 'POST', 'http.url' => '/api/orders', 'http.status_code' => 201],
            resourceAttributes: ['service.name' => 'order-service', 'service.version' => '1.2.0'],
        ));

        $validateSpanId = bin2hex(random_bytes(8));
        $collector->collect(new SpanRecord(
            traceId: $traceId,
            spanId: $validateSpanId,
            parentSpanId: $rootSpanId,
            operationName: 'validate-order',
            serviceName: 'order-service',
            startTime: $now + 0.005,
            endTime: $now + 0.025,
            duration: 20.0,
            status: 'OK',
            kind: 'INTERNAL',
            attributes: ['order.items_count' => 3],
        ));

        $dbSpanId = bin2hex(random_bytes(8));
        $collector->collect(new SpanRecord(
            traceId: $traceId,
            spanId: $dbSpanId,
            parentSpanId: $rootSpanId,
            operationName: 'INSERT orders',
            serviceName: 'order-service',
            startTime: $now + 0.030,
            endTime: $now + 0.080,
            duration: 50.0,
            status: 'OK',
            kind: 'CLIENT',
            attributes: [
                'db.system' => 'postgresql',
                'db.statement' => 'INSERT INTO orders (user_id, total) VALUES ($1, $2)',
            ],
        ));

        $notifySpanId = bin2hex(random_bytes(8));
        $collector->collect(new SpanRecord(
            traceId: $traceId,
            spanId: $notifySpanId,
            parentSpanId: $rootSpanId,
            operationName: 'send-notification',
            serviceName: 'notification-service',
            startTime: $now + 0.100,
            endTime: $now + 0.230,
            duration: 130.0,
            status: 'ERROR',
            statusMessage: 'SMTP connection timeout',
            kind: 'CLIENT',
            attributes: ['messaging.system' => 'smtp', 'messaging.destination' => 'orders@example.com'],
            events: [
                [
                    'name' => 'exception',
                    'timestamp' => $now + 0.228,
                    'attributes' => [
                        'exception.type' => 'SmtpException',
                        'exception.message' => 'Connection timed out after 5s',
                    ],
                ],
            ],
        ));

        return ['fixture' => 'opentelemetry', 'status' => 'ok', 'traceId' => $traceId];
    }
}
