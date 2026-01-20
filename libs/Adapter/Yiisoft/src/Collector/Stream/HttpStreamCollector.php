<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Stream;

use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

final class HttpStreamCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    public function __construct(
        private readonly array $ignoredPathPatterns = [],
        private readonly array $ignoredClasses = [],
        private readonly array $ignoredUrls = []
    ) {
    }

    private array $requests = [];

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->requests;
    }

    public function startup(): void
    {
        $this->isActive = true;
        HttpStreamProxy::register();
        HttpStreamProxy::$ignoredPathPatterns = $this->ignoredPathPatterns;
        HttpStreamProxy::$ignoredClasses = $this->ignoredClasses;
        HttpStreamProxy::$ignoredUrls = $this->ignoredUrls;
        HttpStreamProxy::$collector = $this;

        // TODO: add cURL support, maybe through proxy?
        // https://github.com/php/php-src/issues/10509
        //stream_context_set_default([
        //    'http' => [
        //        'proxy' => 'yii-debug-http://127.0.0.1',
        //    ],
        //]);
    }

    public function shutdown(): void
    {
        HttpStreamProxy::unregister();
        HttpStreamProxy::$collector = null;

        $this->reset();
        $this->isActive = false;
    }

    public function collect(string $operation, string $path, array $args): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->requests[$operation][] = [
            'uri' => $path,
            'args' => $args,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'http_stream' => array_merge(
                ...array_map(
                    fn (string $operation) => [
                        $operation => is_countable($this->requests[$operation]) ? count(
                            $this->requests[$operation]
                        ) : 0,
                    ],
                    array_keys($this->requests)
                )
            ),
        ];
    }

    private function reset(): void
    {
        $this->requests = [];
    }
}
