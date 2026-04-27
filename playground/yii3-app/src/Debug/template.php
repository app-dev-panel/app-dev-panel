<?php

declare(strict_types=1);

/**
 * SSR Log Panel — server-rendered fragment loaded into the ADP debug panel.
 *
 * The template emits **only structure + semantic classes**. All visual styling
 * (typography, colors, spacing, dark mode) lives on the frontend in
 * `packages/panel/src/Module/Debug/Component/Panel/SsrPanel.tsx`, which scopes
 * its rules to `.adp-ssr-panel` and reads `data-level` to color severity badges
 * via MUI theme tokens.
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
?>
<div class="adp-ssr-panel adp-ssr-logs">
    <div class="adp-ssr-logs__header">
        <h2 class="adp-ssr-logs__title">
            <?= count($logs) ?> log <?= count($logs) === 1 ? 'entry' : 'entries' ?>
        </h2>
        <span class="adp-ssr-logs__badge">SSR · backend-rendered</span>
    </div>

    <?php if (count($presentLevels) > 1): ?>
        <div class="adp-ssr-logs__chips">
            <?php foreach ($presentLevels as $level): ?>
                <span class="adp-ssr-logs__chip" data-level="<?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>">
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
                $time = (float) ($entry['time'] ?? 0);
                $line = (string) ($entry['line'] ?? '');
                $context = $entry['context'] ?? null;
                $hasDetails = $line !== '' || is_array($context) && $context !== [];
                ?>
                <details class="adp-ssr-logs__row">
                    <summary class="adp-ssr-logs__summary">
                        <span class="adp-ssr-logs__time"><?= htmlspecialchars(
                            $formatMicrotime($time),
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?></span>
                        <span class="adp-ssr-logs__level" data-level="<?= htmlspecialchars(
                            $level,
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?>">
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
                                <pre class="adp-ssr-logs__detail-context"><?= htmlspecialchars(
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
