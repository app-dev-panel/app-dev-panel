<?php

declare(strict_types=1);

use AppDevPanel\Kernel\Slot\Slot;

/**
 * Server-rendered view for {@see \AppDevPanel\Kernel\Collector\EventCollector}.
 *
 * Visual styling lives in `SsrPanel.uiKit.ts`. Interactive primitives (filter,
 * chips, tooltip, empty-state) are emitted as `Slot::*` markers and hydrated
 * into MUI components after mount.
 *
 * @var list<array{name: string, event: mixed, file: string|false, line: string, time: float|int}> $data
 */

$events = $data;

$shortClass = static function (string $fqcn): string {
    $pos = strrpos($fqcn, '\\');
    return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
};

$colorIndex = static function (string $name): int {
    $hash = 0;
    $len = strlen($name);
    for ($i = 0; $i < $len; $i++) {
        $hash = (($hash * 31) + ord($name[$i])) & 0x7FFFFFFF;
    }
    return $hash % 4;
};

$formatMicrotime = static function (float|int $ts): string {
    $seconds = (int) $ts;
    $micro = (int) round(($ts - $seconds) * 1_000_000);
    return date('H:i:s', $seconds) . '.' . str_pad((string) $micro, 6, '0', STR_PAD_LEFT);
};

$formatDelta = static function (float $ms): string {
    if ($ms < 1) {
        return '+' . (int) round($ms * 1000) . 'µs';
    }
    if ($ms < 1000) {
        return '+' . number_format($ms, 1, '.', '') . 'ms';
    }
    return '+' . number_format($ms / 1000, 2, '.', '') . 's';
};

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// Pre-compute counts for the multi-select chip filter.
$counts = [];
foreach ($events as $entry) {
    $short = $shortClass((string) ($entry['name'] ?? ''));
    $counts[$short] = ($counts[$short] ?? 0) + 1;
}
arsort($counts);
$chipItems = [];
foreach ($counts as $name => $count) {
    $chipItems[] = ['value' => $name, 'label' => $name, 'count' => $count];
}
?>
<?php if ($events === []): ?>
    <?= Slot::emptyState('bolt', 'No dispatched events found') ?>
<?php else: ?>
    <div>
        <div class="adp-ui-toolbar">
            <span class="adp-ui-toolbar__label">
                <?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?>
            </span>
            <span class="adp-ui-toolbar__actions">
                <?= Slot::filter('.adp-ui-event-row', 'Filter events…') ?>
            </span>
        </div>

        <?php if (count($chipItems) > 1): ?>
            <div class="adp-ui-row adp-ui-row--wrap" style="gap: 6px; padding: 12px 12px 0;">
                <?= Slot::chips('.adp-ui-event-row', 'data-tag', $chipItems) ?>
            </div>
        <?php endif; ?>

        <div class="adp-ui-card adp-ui-list">
            <?php $prevTime = null;
            foreach ($events as $entry):
                $name = (string) ($entry['name'] ?? '');
                $short = $shortClass($name);
                $event = $entry['event'] ?? null;
                $line = (string) ($entry['line'] ?? '');
                $fileShort = $line !== '' ? basename(preg_replace('/[:#]\d+$/', '', $line) ?? $line) : '';
                $time = (float) ($entry['time'] ?? 0);
                $deltaMs = $prevTime !== null ? max(0, ($time - $prevTime) * 1000) : null;
                $prevTime = $time;
                $idx = $colorIndex($short);
                $hasDetails = $line !== '' || $event !== null;
                $searchHaystack = strtolower($name . ' ' . $line);
                ?>
                <details class="adp-ui-details adp-ui-event-row"
                         data-tag="<?= $h($short) ?>"
                         data-color-index="<?= $idx ?>"
                         data-search="<?= $h($searchHaystack) ?>">
                    <summary class="adp-ui-accent-bar">
                        <span class="adp-ui-mono adp-ui-text-disabled" style="width: 110px; flex-shrink: 0; padding-top: 2px; font-size: 11px;">
                            <?= $h($formatMicrotime($time)) ?>
                        </span>
                        <span class="adp-ui-dot" data-color-index="<?= $idx ?>" style="margin-top: 6px;"></span>
                        <span class="adp-ui-fill" style="font-size: 13px;">
                            <?= Slot::attrs('class-name', ['fqcn' => $name], $short) ?>
                        </span>
                        <?php if ($deltaMs !== null): ?>
                            <span class="adp-ui-mono adp-ui-text-disabled adp-ui-hide-sm" style="font-size: 10px; flex-shrink: 0; padding-top: 2px;">
                                <?= Slot::tooltip('Time since previous event', $formatDelta($deltaMs)) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($fileShort !== ''): ?>
                            <span class="adp-ui-mono adp-ui-text-disabled adp-ui-hide-sm" style="font-size: 11px; flex-shrink: 0; padding-top: 2px;">
                                <?= $h($fileShort) ?>
                            </span>
                        <?php endif; ?>
                        <span class="adp-ui-caret" aria-hidden="true" style="font-size: 12px; padding-top: 3px;">
                            <?= $hasDetails ? '&#9662;' : '&middot;' ?>
                        </span>
                    </summary>
                    <?php if ($hasDetails): ?>
                        <div class="adp-ui-card-section adp-ui-card--inset" style="padding-left: 134px; border-top: 1px solid; border-color: inherit; font-size: 12px;">
                            <?php if ($line !== ''): ?>
                                <div style="margin-bottom: 8px; word-break: break-all;">
                                    <?= Slot::attrs('file-link', ['path' => $line], $line, 'a') ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($event !== null): ?>
                                <?= Slot::json('json', $event) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif;
