<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

final class CollectorDataResolver
{
    private const COLLECTOR_NAME_MAP = [
        'logger' => 'LogCollector',
        'event' => 'EventCollector',
        'exception' => 'ExceptionCollector',
        'http' => 'HttpClientCollector',
        'service' => 'ServiceCollector',
        'timeline' => 'TimelineCollector',
        'var-dumper' => 'VarDumperCollector',
        'request' => 'RequestCollector',
        'web' => 'WebAppInfoCollector',
        'command' => 'CommandCollector',
        'console' => 'ConsoleAppInfoCollector',
        'fs_stream' => 'FilesystemStreamCollector',
        'http_stream' => 'HttpStreamCollector',
        'cache' => 'CacheCollector',
        'security' => 'AuthorizationCollector',
        'twig' => 'TemplateCollector',
        'doctrine' => 'DoctrineCollector',
        'mailer' => 'MailerCollector',
        'db' => 'DatabaseCollector',
        'queue' => 'QueueCollector',
        'middleware' => 'MiddlewareCollector',
        'router' => 'RouterCollector',
        'validator' => 'ValidatorCollector',
        'template' => 'TemplateCollector',
        'assets' => 'AssetBundleCollector',
        'opentelemetry' => 'OpenTelemetryCollector',
        'elasticsearch' => 'ElasticsearchCollector',
        'redis' => 'RedisCollector',
        'coverage' => 'CodeCoverageCollector',
    ];

    /**
     * @param array<string, mixed> $debugData
     *
     * @return array<array-key, mixed>|null
     */
    public function resolve(array $debugData, string $collectorName): ?array
    {
        return (
            $this->findByDirectKey($debugData, $collectorName) ?? $this->findByClassName(
                $debugData,
                $collectorName,
            ) ?? $this->findByPartialMatch($debugData, $collectorName)
        );
    }

    /**
     * @param array<string, mixed> $debugData
     *
     * @return array<array-key, mixed>|null
     */
    private function findByDirectKey(array $debugData, string $collectorName): ?array
    {
        if (!array_key_exists($collectorName, $debugData)) {
            return null;
        }

        $value = $debugData[$collectorName];

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $debugData
     *
     * @return array<array-key, mixed>|null
     */
    private function findByClassName(array $debugData, string $collectorName): ?array
    {
        $className = self::COLLECTOR_NAME_MAP[$collectorName] ?? null;
        if ($className === null) {
            return null;
        }

        foreach ($debugData as $key => $value) {
            if (is_string($key) && str_contains($key, $className) && is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $debugData
     *
     * @return array<array-key, mixed>|null
     */
    private function findByPartialMatch(array $debugData, string $collectorName): ?array
    {
        foreach ($debugData as $key => $value) {
            if (is_string($key) && is_array($value) && str_contains(strtolower($key), strtolower($collectorName))) {
                return $value;
            }
        }

        return null;
    }
}
