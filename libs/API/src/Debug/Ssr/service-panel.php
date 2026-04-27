<?php

declare(strict_types=1);

use AppDevPanel\Api\Debug\Slot\Slot;

/**
 * SSR Service panel — server-rendered fragment.
 *
 * Renders the ServiceCollector method-call records mirrored by
 * SsrServicePanelCollector. Service id / class hydrate into `<ClassName>`
 * (with Inspector + Open-in-Editor buttons), arguments and result hydrate
 * into `<JsonRenderer>`, status flips chip color via `data-severity`.
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
    if ($ms < 1) {
        return number_format((float) $ms, 2, '.', '') . ' ms';
    }
    if ($ms < 1000) {
        return (string) (int) round((float) $ms) . ' ms';
    }
    return number_format((float) $ms / 1000, 2, '.', '') . ' s';
};

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$total = count($items);
$errorCount = 0;
foreach ($items as $entry) {
    if (($entry['status'] ?? '') !== 'success') {
        $errorCount++;
    }
}
?>
<div class="adp-ui-stack">
    <div class="adp-ui-row adp-ui-row--center adp-ui-row--between">
        <span class="adp-ui-text-secondary adp-ui-text-strong">
            <?= $total ?> service call<?= $total === 1 ? '' : 's' ?>
            <?php if ($errorCount > 0): ?>
                · <span data-severity="error" style="color: var(--adp-sev);"><?= $errorCount ?> failed</span>
            <?php endif; ?>
        </span>
        <span class="adp-ui-badge">SSR · backend-rendered</span>
    </div>

    <?php if ($items === []): ?>
        <div class="adp-ui-empty">No service calls were tracked for this entry.</div>
    <?php else: ?>
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
                $hasDetails = $hasArgs || $hasResult || $error !== null;
                ?>
                <details class="adp-ui-details">
                    <summary>
                        <span class="adp-ui-mono adp-ui-text-disabled" style="width: 110px; flex-shrink: 0; padding-top: 2px; font-size: 11px;">
                            <?= $h($formatMicrotime($timeStart)) ?>
                        </span>
                        <span class="adp-ui-chip adp-ui-chip--filled" data-severity="<?= $isError
                            ? 'error'
                            : 'info' ?>" style="min-width: 60px; justify-content: center; height: 20px; font-size: 10px; margin-top: 1px; text-transform: uppercase;">
                            <?= $h($isError ? 'error' : 'ok') ?>
                        </span>
                        <span class="adp-ui-fill" style="font-size: 13px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <?= Slot::attrs('class-name', ['fqcn' => $class], $shortClass($class)) ?>
                            <span class="adp-ui-text-disabled adp-ui-mono" style="font-size: 11px;">::<?= $h(
                                $method,
                            ) ?>()</span>
                        </span>
                        <span class="adp-ui-mono adp-ui-text-disabled" style="font-size: 11px; flex-shrink: 0; padding-top: 2px;">
                            <?= $h($formatDuration($durationMs)) ?>
                        </span>
                        <span class="adp-ui-caret" aria-hidden="true" style="font-size: 12px; padding-top: 3px;">
                            <?= $hasDetails ? '&#9662;' : '&middot;' ?>
                        </span>
                    </summary>
                    <?php if ($hasDetails): ?>
                        <div class="adp-ui-card-section adp-ui-card--inset" style="padding-left: 134px; border-top: 1px solid; border-color: inherit; font-size: 12px;">
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
                                    <?= Slot::json('json', $args) ?>
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
    <?php endif; ?>
</div>
