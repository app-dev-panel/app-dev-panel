<?php

declare(strict_types=1);

/**
 * SSR Log Panel — server-rendered fragment loaded into the ADP debug panel.
 *
 * Embedded inline (no `<html>` shell) inside the panel's collector content area.
 * The wrapper class scopes all selectors so the styles don't leak into the SPA.
 *
 * @var list<array{time: float|int, level: string, message: mixed, context: mixed, line: string}> $data
 */

$logs = $data;

$severityOrder = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

$levelColor = static fn(string $level): string => match ($level) {
    'emergency', 'alert', 'critical', 'error' => '#dc2626',
    'warning' => '#d97706',
    'notice' => '#2563eb',
    'info' => '#16a34a',
    default => '#6b7280',
};

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
?>
<style>
.adp-ssr-logs { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: inherit; padding: 0; }
.adp-ssr-logs__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.adp-ssr-logs__title { font-size: 13px; font-weight: 600; color: rgba(0,0,0,.6); margin: 0; }
.adp-ssr-logs__badge { font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; background: rgba(37, 99, 235, 0.12); color: #1d4ed8; padding: 3px 8px; border-radius: 4px; }
.adp-ssr-logs__chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.adp-ssr-logs__chip { display: inline-flex; align-items: center; height: 22px; padding: 0 10px; border-radius: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; border: 1px solid; background: transparent; color: inherit; }
.adp-ssr-logs__list { border: 1px solid rgba(0,0,0,.08); border-radius: 6px; overflow: hidden; background: #fff; }
.adp-ssr-logs__row { border-bottom: 1px solid rgba(0,0,0,.08); }
.adp-ssr-logs__row:last-child { border-bottom: none; }
.adp-ssr-logs__row > summary { display: flex; align-items: flex-start; gap: 12px; padding: 8px 12px; cursor: pointer; list-style: none; transition: background 0.1s ease; }
.adp-ssr-logs__row > summary::-webkit-details-marker { display: none; }
.adp-ssr-logs__row > summary:hover, .adp-ssr-logs__row[open] > summary { background: rgba(0,0,0,.03); }
.adp-ssr-logs__time { font-family: 'SF Mono', 'JetBrains Mono', Consolas, monospace; font-size: 11px; flex-shrink: 0; width: 110px; padding-top: 2px; color: rgba(0,0,0,.5); }
.adp-ssr-logs__level { display: inline-flex; align-items: center; justify-content: center; min-width: 64px; height: 20px; padding: 0 8px; border-radius: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; color: #fff; flex-shrink: 0; margin-top: 1px; }
.adp-ssr-logs__message { flex: 1; font-size: 13px; word-break: break-word; line-height: 1.5; }
.adp-ssr-logs__caret { flex-shrink: 0; color: rgba(0,0,0,.4); font-size: 12px; padding-top: 3px; transition: transform 0.15s ease; }
.adp-ssr-logs__row[open] .adp-ssr-logs__caret { transform: rotate(180deg); }
.adp-ssr-logs__detail { padding: 12px 12px 16px 134px; background: rgba(0,0,0,.03); border-top: 1px solid rgba(0,0,0,.08); font-size: 12px; }
.adp-ssr-logs__detail-line { font-family: 'SF Mono', 'JetBrains Mono', Consolas, monospace; color: #2563eb; margin-bottom: 8px; word-break: break-all; }
.adp-ssr-logs__detail pre { background: #1e1e2e; color: #cdd6f4; padding: 12px; border-radius: 6px; font-family: 'SF Mono', 'JetBrains Mono', Consolas, monospace; font-size: 12px; line-height: 1.5; overflow-x: auto; margin: 0; white-space: pre-wrap; word-break: break-word; }
.adp-ssr-logs__empty { padding: 32px; text-align: center; color: rgba(0,0,0,.5); font-size: 14px; border: 1px dashed rgba(0,0,0,.15); border-radius: 6px; }
@media (prefers-color-scheme: dark) {
    .adp-ssr-logs__title { color: rgba(255,255,255,.7); }
    .adp-ssr-logs__list { background: #1e293b; border-color: rgba(255,255,255,.08); }
    .adp-ssr-logs__row { border-color: rgba(255,255,255,.08); }
    .adp-ssr-logs__row > summary:hover, .adp-ssr-logs__row[open] > summary { background: rgba(255,255,255,.04); }
    .adp-ssr-logs__time { color: rgba(255,255,255,.5); }
    .adp-ssr-logs__detail { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
    .adp-ssr-logs__caret { color: rgba(255,255,255,.4); }
    .adp-ssr-logs__empty { color: rgba(255,255,255,.5); border-color: rgba(255,255,255,.15); }
}
</style>
<div class="adp-ssr-logs">
    <div class="adp-ssr-logs__header">
        <h2 class="adp-ssr-logs__title">
            <?= count($logs) ?> log <?= count($logs) === 1 ? 'entry' : 'entries' ?>
        </h2>
        <span class="adp-ssr-logs__badge">SSR · backend-rendered</span>
    </div>

    <?php if (count($presentLevels) > 1): ?>
        <div class="adp-ssr-logs__chips">
            <?php foreach ($presentLevels as $level):
                $color = $levelColor($level);
                ?>
                <span class="adp-ssr-logs__chip" style="color: <?= htmlspecialchars(
                    $color,
                    ENT_QUOTES,
                ) ?>; border-color: <?= htmlspecialchars($color, ENT_QUOTES) ?>;">
                    <?= htmlspecialchars(strtoupper($level), ENT_QUOTES, 'UTF-8') ?>
                    (<?= (int) $counts[$level] ?>)
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($logs === []): ?>
        <div class="adp-ssr-logs__empty">No logs were collected for this entry.</div>
    <?php else: ?>
        <div class="adp-ssr-logs__list">
            <?php foreach ($logs as $entry):
                $level = (string) ($entry['level'] ?? 'debug');
                $color = $levelColor($level);
                $time = (float) ($entry['time'] ?? 0);
                $line = (string) ($entry['line'] ?? '');
                $context = $entry['context'] ?? null;
                $hasDetails = $line !== '' || is_array($context) && $context !== [];
                ?>
                <details class="adp-ssr-logs__row">
                    <summary>
                        <span class="adp-ssr-logs__time"><?= htmlspecialchars(
                            $formatMicrotime($time),
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?></span>
                        <span class="adp-ssr-logs__level" style="background: <?= htmlspecialchars(
                            $color,
                            ENT_QUOTES,
                        ) ?>;">
                            <?= htmlspecialchars(strtoupper($level), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="adp-ssr-logs__message"><?= htmlspecialchars(
                            $formatMessage($entry['message'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?></span>
                        <span class="adp-ssr-logs__caret" aria-hidden="true"><?= $hasDetails
                            ? '&#9662;'
                            : '&middot;' ?></span>
                    </summary>
                    <?php if ($hasDetails): ?>
                        <div class="adp-ssr-logs__detail">
                            <?php if ($line !== ''): ?>
                                <div class="adp-ssr-logs__detail-line"><?= htmlspecialchars(
                                    $line,
                                    ENT_QUOTES,
                                    'UTF-8',
                                ) ?></div>
                            <?php endif; ?>
                            <?php if (is_array($context) && $context !== []): ?>
                                <pre><?= htmlspecialchars(
                                    json_encode(
                                        $context,
                                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                                    ) ?: '',
                                    ENT_QUOTES,
                                    'UTF-8',
                                ) ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
