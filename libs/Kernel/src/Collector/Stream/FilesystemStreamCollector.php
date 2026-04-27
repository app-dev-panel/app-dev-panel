<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Stream;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;

final class FilesystemStreamCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    public function __construct(
        /**
         * Collection of regexps to ignore files sources to sniff.
         * Examples:
         * - '/' . preg_quote('app-dev-panel/src/Dumper', '/') . '/'
         * - '/ClosureExporter/'
         */
        private readonly array $ignoredPathPatterns = [],
        private readonly array $ignoredClasses = [],
    ) {}

    /**
     * @var array[]
     */
    private array $operations = [];

    public function getCollected(): array
    {
        return array_map('array_values', $this->operations);
    }

    public function startup(): void
    {
        $this->reset();
        $this->isActive = true;
        FilesystemStreamProxy::register();
        FilesystemStreamProxy::$collector = $this;
        FilesystemStreamProxy::$ignoredPathPatterns = $this->ignoredPathPatterns;
        FilesystemStreamProxy::$ignoredClasses = $this->ignoredClasses;
    }

    public function shutdown(): void
    {
        // Detach the stream wrapper FIRST so the upcoming storage flush (which
        // writes data.json.gz / objects.json.gz / summary.json through file://)
        // does not feed itself back into $operations. The buffer is preserved so
        // `getCollected()` / `getSummary()` still see the pre-flush snapshot.
        FilesystemStreamProxy::unregister();
        FilesystemStreamProxy::$collector = null;
        FilesystemStreamProxy::$ignoredPathPatterns = [];
        FilesystemStreamProxy::$ignoredClasses = [];

        $this->isActive = false;
    }

    public function collect(string $operation, string $path, array $args): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->operations[$operation][] = [
            'path' => $path,
            'args' => $args,
        ];
    }

    public function getSummary(): array
    {
        return [
            'fs_stream' => array_map(fn(array $operations) => count($operations), $this->operations),
        ];
    }

    private function reset(): void
    {
        $this->operations = [];
    }
}
