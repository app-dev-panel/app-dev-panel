<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SpanProcessorInterface::class, static function (): SpanProcessorInterface {
            // No-op exporter — spans are captured by ADP's SpanProcessorInterfaceProxy,
            // no need to export them anywhere.
            return new SimpleSpanProcessor(new \OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter());
        });

        $this->app->singleton(TracerProviderInterface::class, function (): TracerProviderInterface {
            return TracerProvider::builder()
                ->addSpanProcessor($this->app->make(SpanProcessorInterface::class))
                ->setResource(\OpenTelemetry\SDK\Resource\ResourceInfo::create(\OpenTelemetry\SDK\Common\Attribute\Attributes::create([
                    'service.name' => 'order-service',
                    'service.version' => '1.2.0',
                ])))
                ->build();
        });
    }

    public function boot(): void
    {
        //
    }
}
