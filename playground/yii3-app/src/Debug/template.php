<?php

declare(strict_types=1);

use AppDevPanel\Api\Debug\Slot\Slot;

/**
 * SSR Log Panel — server-rendered fragment.
 *
 * Uses **only** the generic `adp-ui-*` UI kit shipped by `SsrPanel` (see
 * `libs/frontend/packages/panel/src/Module/Debug/Component/Panel/SsrPanel.uiKit.ts`).
 * Live React primitives (file links, structured payloads, class names…) are
 * embedded via the `Slot::*` helper — the panel hydrates each marker into the
 * matching React component on mount, with full theme/Redux/router context.
 *
 * The template carries no colors and no theme-mode awareness — severity tinting
 * is driven by `data-severity="…"` attributes, layout numbers that are genuinely
 * log-specific (the time column width, the indent of the detail block) ride on
 * inline `style` attributes.
 *
 * @var list<array{time: float|int, level: string, message: mixed, context: mixed, line: string}> $data
 */

$logs = $data;
$severityOrder = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

$formatMicrotime = static function (float|int $ts): string {
    $seconds = (int) $ts;
    $micro = (int) round(($ts - $seconds) * 1_000_000);
    return date('H:i:s', $seconds) . '.' . str_pad((string) $micro, 6, '0', STR_PAD_LEFT);
};

$formatMessage = static function (mixed $message): string {
    if (is_string($message)) {
        return $message;
    }
    return json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
};

$counts = [];
foreach ($logs as $entry) {
    $level = (string) ($entry['level'] ?? 'debug');
    $counts[$level] = ($counts[$level] ?? 0) + 1;
}
$presentLevels = array_values(array_filter($severityOrder, static fn(string $l): bool => isset($counts[$l])));

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<div class="adp-ui-stack">
    <div class="adp-ui-row adp-ui-row--center adp-ui-row--between">
        <span class="adp-ui-text-secondary adp-ui-text-strong">
            <?= count($logs) ?> log <?= count($logs) === 1 ? 'entry' : 'entries' ?>
        </span>
        <span class="adp-ui-badge">SSR · backend-rendered</span>
    </div>

    <?php if (count($presentLevels) > 1): ?>
        <div class="adp-ui-row adp-ui-row--wrap" style="gap: 6px;">
            <?php foreach ($presentLevels as $level): ?>
                <span class="adp-ui-chip" data-severity="<?= $h($level) ?>">
                    <?= $h(strtoupper($level)) ?> (<?= (int) $counts[$level] ?>)
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($logs === []): ?>
        <div class="adp-ui-empty">No logs were collected for this entry.</div>
    <?php else: ?>
        <div class="adp-ui-card adp-ui-list">
            <?php foreach ($logs as $entry):
                $level = (string) ($entry['level'] ?? 'debug');
                $time = (float) ($entry['time'] ?? 0);
                $line = (string) ($entry['line'] ?? '');
                $context = $entry['context'] ?? null;
                $hasDetails = $line !== '' || is_array($context) && $context !== [];
                ?>
                <details class="adp-ui-details">
                    <summary>
                        <span class="adp-ui-mono adp-ui-text-disabled" style="width: 110px; flex-shrink: 0; padding-top: 2px; font-size: 11px;">
                            <?= $h($formatMicrotime($time)) ?>
                        </span>
                        <span class="adp-ui-chip adp-ui-chip--filled" data-severity="<?= $h(
                            $level,
                        ) ?>" style="min-width: 64px; justify-content: center; height: 20px; font-size: 10px; margin-top: 1px;">
                            <?= $h(strtoupper($level)) ?>
                        </span>
                        <span class="adp-ui-fill" style="font-size: 13px;">
                            <?= $h($formatMessage($entry['message'] ?? '')) ?>
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
                            <?php if (is_array($context) && $context !== []): ?>
                                <?= Slot::json('json', $context) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
