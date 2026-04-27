<?php

declare(strict_types=1);

use AppDevPanel\Api\Debug\Slot\Slot;

/**
 * SSR Event panel — server-rendered fragment.
 *
 * Renders the EventCollector items mirrored by SsrEventPanelCollector. All
 * styling lives in the shared `adp-ui-*` UI kit (`SsrPanel.uiKit.ts`); this
 * template only emits structure + slot markers, which the React host hydrates
 * into real `<ClassName>`/`<JsonRenderer>`/`<FileLink>` instances.
 *
 * @var list<array{name: string, event: mixed, file: string|false, line: string, time: float|int}> $data
 */

$events = $data;

$shortClass = static function (string $fqcn): string {
    $pos = strrpos($fqcn, '\\');
    return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
};

$formatMicrotime = static function (float|int $ts): string {
    $seconds = (int) $ts;
    $micro = (int) round(($ts - $seconds) * 1_000_000);
    return date('H:i:s', $seconds) . '.' . str_pad((string) $micro, 6, '0', STR_PAD_LEFT);
};

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<div class="adp-ui-stack">
    <div class="adp-ui-row adp-ui-row--center adp-ui-row--between">
        <span class="adp-ui-text-secondary adp-ui-text-strong">
            <?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?>
        </span>
        <span class="adp-ui-badge">SSR · backend-rendered</span>
    </div>

    <?php if ($events === []): ?>
        <div class="adp-ui-empty">No events were dispatched for this entry.</div>
    <?php else: ?>
        <div class="adp-ui-card adp-ui-list">
            <?php foreach ($events as $entry):
                $name = (string) ($entry['name'] ?? '');
                $event = $entry['event'] ?? null;
                $line = (string) ($entry['line'] ?? '');
                $time = (float) ($entry['time'] ?? 0);
                $hasDetails = $line !== '' || $event !== null;
                ?>
                <details class="adp-ui-details">
                    <summary>
                        <span class="adp-ui-mono adp-ui-text-disabled" style="width: 110px; flex-shrink: 0; padding-top: 2px; font-size: 11px;">
                            <?= $h($formatMicrotime($time)) ?>
                        </span>
                        <span class="adp-ui-fill" style="font-size: 13px;">
                            <?= Slot::attrs('class-name', ['fqcn' => $name], $shortClass($name)) ?>
                        </span>
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
    <?php endif; ?>
</div>
