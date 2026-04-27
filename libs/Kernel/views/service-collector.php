<?php

declare(strict_types=1);

use AppDevPanel\Kernel\Slot\Slot;

/**
 * Server-rendered view for {@see \AppDevPanel\Kernel\Collector\ServiceCollector}.
 *
 * Two tabs hydrated by the `tabs` slot:
 *   - Summary — aggregated by class::method (count, errors, total/max/avg time)
 *   - All     — every recorded call with status / duration / arguments / result
 *
 * @var list<array{
 *     service: string,
 *     class: string,
 *     method: string,
 *     arguments: mixed,
 *     result: mixed,
 *     status: string,
 *     error: ?string,
 *     timeStart: float|int,
 *     timeEnd: float|int,
 * }> $data
 */

$items = $data;

$shortClass = static function (string $fqcn): string {
    $pos = strrpos($fqcn, '\\');
    return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
};

$formatMicrotime = static function (float|int $ts): string {
    $seconds = (int) $ts;
    $micro = (int) round(($ts - $seconds) * 1_000_000);
    return date('H:i:s', $seconds) . '.' . str_pad((string) $micro, 6, '0', STR_PAD_LEFT);
};

$formatDuration = static function (float|int $ms): string {
    $ms = (float) $ms;
    if ($ms < 1) {
        return number_format($ms, 2, '.', '') . ' ms';
    }
    if ($ms < 1000) {
        return (string) (int) round($ms) . ' ms';
    }
    return number_format($ms / 1000, 2, '.', '') . ' s';
};

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<?php if ($items === []): ?>
    <?= Slot::emptyState('miscellaneous_services', 'No spied services found') ?>
<?php else: ?>
    <?php

    // --- aggregate for Summary tab ---
    $summary = [];
    foreach ($items as $entry) {
        $class = (string) ($entry['class'] ?? '');
        $method = (string) ($entry['method'] ?? '');
        $key = $class . '::' . $method;
        $duration = ((float) ($entry['timeEnd'] ?? 0) - (float) ($entry['timeStart'] ?? 0)) * 1000;
        $isOk = ($entry['status'] ?? '') === 'success';
        if (!isset($summary[$key])) {
            $summary[$key] = [
                'class' => $class,
                'method' => $method,
                'count' => 0,
                'errors' => 0,
                'total' => 0.0,
                'max' => 0.0,
                'times' => [],
            ];
        }
        $summary[$key]['count']++;
        if (!$isOk) {
            $summary[$key]['errors']++;
        }
        $summary[$key]['total'] += $duration;
        $summary[$key]['max'] = max($summary[$key]['max'], $duration);
        $summary[$key]['times'][] = $duration;
    }
    uasort($summary, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);

    $totalCalls = count($items);
    $totalErrors = 0;
    foreach ($items as $e) {
        if (($e['status'] ?? '') !== 'success') {
            $totalErrors++;
        }
    }
    ?>

    <?= Slot::tabs([
        ['value' => 'summary', 'label' => 'Summary'],
        ['value' => 'all', 'label' => 'All (' . $totalCalls . ')'],
    ]) ?>

    <section data-adp-tab-panel="summary">
        <div class="adp-ui-stack">
            <span class="adp-ui-text-secondary adp-ui-text-strong">
                <?= count($summary) ?> unique method<?= count($summary) === 1 ? '' : 's' ?>
                <?php if ($totalErrors > 0): ?>
                    · <span data-severity="error" style="color: var(--adp-sev);"><?= $totalErrors ?> failed</span>
                <?php endif; ?>
            </span>
            <div class="adp-ui-card adp-ui-list">
                <?php foreach ($summary as $row):
                    $count = $row['count'];
                    $errors = $row['errors'];
                    $total = $row['total'];
                    $avg = $count > 0 ? $total / $count : 0;
                    ?>
                    <details class="adp-ui-details">
                        <summary>
                            <span class="adp-ui-fill" style="font-size: 13px;">
                                <?= Slot::attrs(
                                    'class-name',
                                    ['fqcn' => $row['class'], 'method' => $row['method']],
                                    $shortClass($row['class']) . '::' . $row['method'] . '()',
                                ) ?>
                            </span>
                            <span class="adp-ui-chip" style="height: 20px; font-size: 10px;">
                                <?= $count ?> call<?= $count === 1 ? '' : 's' ?>
                            </span>
                            <?php if ($errors > 0): ?>
                                <span class="adp-ui-chip" data-severity="error" style="height: 20px; font-size: 10px;">
                                    <?= $errors ?> err
                                </span>
                            <?php endif; ?>
                            <span class="adp-ui-mono adp-ui-text-disabled adp-ui-hide-sm" style="font-size: 11px; flex-shrink: 0; padding-top: 2px; min-width: 80px; text-align: right;">
                                <?= $h($formatDuration($total)) ?>
                            </span>
                            <span class="adp-ui-caret" aria-hidden="true" style="font-size: 12px; padding-top: 3px;">&#9662;</span>
                        </summary>
                        <div class="adp-ui-card-section adp-ui-card--inset" style="border-top: 1px solid; border-color: inherit; font-size: 12px;">
                            <span class="adp-ui-text-disabled adp-ui-mono" style="margin-right: 16px;">Total: <?= $h($formatDuration(
                                $total,
                            )) ?></span>
                            <span class="adp-ui-text-disabled adp-ui-mono" style="margin-right: 16px;">Max: <?= $h($formatDuration(
                                $row['max'],
                            )) ?></span>
                            <span class="adp-ui-text-disabled adp-ui-mono">Avg: <?= $h($formatDuration($avg)) ?></span>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section data-adp-tab-panel="all">
        <div class="adp-ui-stack">
            <div class="adp-ui-row adp-ui-row--center adp-ui-row--between adp-ui-row--wrap">
                <span class="adp-ui-text-secondary adp-ui-text-strong">
                    <?= $totalCalls ?> service call<?= $totalCalls === 1 ? '' : 's' ?>
                    <?php if ($totalErrors > 0): ?>
                        · <span data-severity="error" style="color: var(--adp-sev);"><?= $totalErrors ?> failed</span>
                    <?php endif; ?>
                </span>
                <?= Slot::filter('.adp-ui-service-row', 'Filter services…') ?>
            </div>
            <div class="adp-ui-card adp-ui-list">
                <?php foreach ($items as $entry):
                    $service = (string) ($entry['service'] ?? '');
                    $class = (string) ($entry['class'] ?? '');
                    $method = (string) ($entry['method'] ?? '');
                    $args = $entry['arguments'] ?? null;
                    $result = $entry['result'] ?? null;
                    $status = (string) ($entry['status'] ?? 'success');
                    $error = $entry['error'] ?? null;
                    $timeStart = (float) ($entry['timeStart'] ?? 0);
                    $timeEnd = (float) ($entry['timeEnd'] ?? 0);
                    $durationMs = ($timeEnd - $timeStart) * 1000;
                    $isError = $status !== 'success';
                    $hasArgs = is_array($args) ? $args !== [] : $args !== null;
                    $hasResult = $result !== null && $result !== [];
                    $hasDetails = $hasArgs || $hasResult || $error !== null || $service !== '' && $service !== $class;
                    $searchHaystack = strtolower($class . '::' . $method . ' ' . $service);
                    ?>
                    <details class="adp-ui-details adp-ui-service-row" data-search="<?= $h($searchHaystack) ?>">
                        <summary>
                            <span class="adp-ui-mono adp-ui-text-disabled adp-ui-hide-sm" style="width: 110px; flex-shrink: 0; padding-top: 2px; font-size: 11px;">
                                <?= $h($formatMicrotime($timeStart)) ?>
                            </span>
                            <span class="adp-ui-chip adp-ui-chip--filled" data-severity="<?= $isError
                                ? 'error'
                                : 'info' ?>" style="min-width: 60px; justify-content: center; height: 20px; font-size: 10px; margin-top: 1px; text-transform: uppercase;">
                                <?= $h($isError ? 'error' : 'ok') ?>
                            </span>
                            <span class="adp-ui-fill" style="font-size: 13px;">
                                <?= Slot::attrs(
                                    'class-name',
                                    ['fqcn' => $class, 'method' => $method],
                                    $shortClass($class) . '::' . $method . '()',
                                ) ?>
                            </span>
                            <span class="adp-ui-mono adp-ui-text-disabled adp-ui-hide-sm" style="font-size: 11px; flex-shrink: 0; padding-top: 2px; min-width: 80px; text-align: right;">
                                <?= $h($formatDuration($durationMs)) ?>
                            </span>
                            <span class="adp-ui-caret" aria-hidden="true" style="font-size: 12px; padding-top: 3px;">
                                <?= $hasDetails ? '&#9662;' : '&middot;' ?>
                            </span>
                        </summary>
                        <?php if ($hasDetails): ?>
                            <div class="adp-ui-card-section adp-ui-card--inset" style="border-top: 1px solid; border-color: inherit; font-size: 12px;">
                                <?php if ($service !== '' && $service !== $class): ?>
                                    <div style="margin-bottom: 8px;">
                                        <span class="adp-ui-text-disabled" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">Service</span>
                                        <div><?= Slot::attrs('class-name', ['fqcn' => $service], $service) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error !== null): ?>
                                    <div data-severity="error" style="color: var(--adp-sev); margin-bottom: 8px; font-family: monospace;">
                                        <?= $h((string) $error) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($hasArgs): ?>
                                    <div style="margin-bottom: 8px;">
                                        <span class="adp-ui-text-disabled" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">Arguments</span>
                                        <?= Slot::json(
                                            'json',
                                            is_array($args) && count($args) === 1 ? array_values($args)[0] : $args,
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($hasResult): ?>
                                    <div>
                                        <span class="adp-ui-text-disabled" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px;">Result</span>
                                        <?= Slot::json('json', $result) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif;
