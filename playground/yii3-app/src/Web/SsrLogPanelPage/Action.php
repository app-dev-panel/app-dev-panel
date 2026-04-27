<?php

declare(strict_types=1);

namespace App\Web\SsrLogPanelPage;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Kernel\Collector\LogCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

/**
 * Server-side rendered demo of the LogCollector panel.
 *
 * Reads LogCollector data from the latest stored debug entry (or one selected by ?id=)
 * and renders it as plain HTML on the backend, mirroring the React LogPanel layout.
 * Filtering by severity and free-text query is done via query string — no JS required.
 */
final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
        private CollectorRepositoryInterface $collectorRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $entries = $this->collectorRepository->getSummary();

        $selectedId = isset($query['id']) && is_string($query['id']) ? $query['id'] : null;
        $currentSummary = $this->resolveCurrentSummary($entries, $selectedId);

        $logs = [];
        $errorMessage = null;

        if ($currentSummary !== null) {
            try {
                $detail = $this->collectorRepository->getDetail($currentSummary['id']);
                $logs = $detail[LogCollector::class] ?? [];
            } catch (NotFoundException $e) {
                $errorMessage = $e->getMessage();
            }
        }

        $activeLevels = $this->parseLevels($query['level'] ?? null);
        $searchTerm = isset($query['q']) && is_string($query['q']) ? trim($query['q']) : '';

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'entries' => $entries,
            'currentSummary' => $currentSummary,
            'logs' => is_array($logs) ? $logs : [],
            'activeLevels' => $activeLevels,
            'searchTerm' => $searchTerm,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array<string, mixed>|null
     */
    private function resolveCurrentSummary(array $entries, ?string $selectedId): ?array
    {
        if ($entries === []) {
            return null;
        }

        if ($selectedId !== null) {
            foreach ($entries as $entry) {
                if (($entry['id'] ?? null) === $selectedId) {
                    return $entry;
                }
            }
        }

        return $entries[0];
    }

    /**
     * @return list<string>
     */
    private function parseLevels(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
        }
        if (is_array($raw)) {
            return array_values(array_filter(array_map(static fn(mixed $v): string => (string) $v, $raw), 'strlen'));
        }
        return [];
    }
}
