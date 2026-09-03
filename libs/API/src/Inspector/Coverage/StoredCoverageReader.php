<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Coverage;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Security\DebugIdValidator;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\CodeCoverageHelper;

/**
 * Reads the {@see CodeCoverageCollector} payload out of stored debug entries.
 *
 * Coverage is recorded while the *application* request runs; it cannot be measured
 * from inside the inspector request, so the API serves what the collector persisted.
 */
final class StoredCoverageReader
{
    private const string SUMMARY_KEY = 'codeCoverage';

    public function __construct(
        private readonly CollectorRepositoryInterface $collectorRepository,
    ) {}

    /**
     * `$requestedId` when given (validated), otherwise the newest entry whose
     * summary carries a `codeCoverage` block; null when there is none.
     *
     * @throws \AppDevPanel\Api\Debug\Exception\BadRequestException on a malformed id
     */
    public function resolveEntryId(mixed $requestedId): ?string
    {
        if (is_string($requestedId) && $requestedId !== '') {
            return DebugIdValidator::assertValid($requestedId, 'debugEntryId');
        }

        /** @var list<array<string, mixed>> $summaries newest first */
        $summaries = $this->collectorRepository->getSummary();

        foreach ($summaries as $summary) {
            if (array_key_exists(self::SUMMARY_KEY, $summary) && DebugIdValidator::isValid($summary['id'] ?? null)) {
                /** @var string */
                return $summary['id'];
            }
        }

        return null;
    }

    /**
     * Collector payload of an entry normalised to `driver`, `files`, `summary`; null when absent.
     *
     * @return array{driver: ?string, files: array<string, mixed>, summary: array<string, mixed>}|null
     */
    public function read(string $entryId): ?array
    {
        $detail = $this->collectorRepository->getDetail($entryId);
        $collected = $detail[CodeCoverageCollector::class] ?? null;

        if (!is_array($collected)) {
            return null;
        }

        $driver = $collected['driver'] ?? null;
        $files = $collected['files'] ?? null;
        $summary = $collected['summary'] ?? null;

        return [
            'driver' => is_string($driver) ? $driver : null,
            'files' => is_array($files) ? $files : [],
            'summary' => is_array($summary) ? $summary : CodeCoverageHelper::buildSummary([], 0, 0),
        ];
    }
}
