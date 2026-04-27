<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var list<array<string, mixed>> $entries
 * @var array<string, mixed>|null $currentSummary
 * @var list<array{time: float|int, level: string, message: mixed, context: mixed, line: string}> $logs
 * @var list<string> $activeLevels
 * @var string $searchTerm
 * @var string|null $errorMessage
 */

$this->setTitle('SSR Log Panel');

$severityOrder = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

$levelColor = static function (string $level): string {
    return match ($level) {
        'emergency', 'alert', 'critical', 'error' => '#dc2626',
        'warning' => '#d97706',
        'notice' => '#4361ee',
        'info' => '#16a34a',
        default => '#6b7280',
    };
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

$buildUrl = static function (array $overrides) use ($urlGenerator, $currentSummary, $activeLevels, $searchTerm): string {
    $params = [];
    if ($currentSummary !== null) {
        $params['id'] = $currentSummary['id'];
    }
    if ($activeLevels !== []) {
        $params['level'] = implode(',', $activeLevels);
    }
    if ($searchTerm !== '') {
        $params['q'] = $searchTerm;
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return $urlGenerator->generate('ssr-log-panel') . ($params === [] ? '' : '?' . http_build_query($params));
};

$counts = [];
foreach ($logs as $entry) {
    $level = (string) ($entry['level'] ?? 'debug');
    $counts[$level] = ($counts[$level] ?? 0) + 1;
}

$searchLower = $searchTerm === '' ? '' : mb_strtolower($searchTerm);
$activeLevelSet = array_flip($activeLevels);

$filteredLogs = [];
foreach ($logs as $entry) {
    $level = (string) ($entry['level'] ?? 'debug');
    if ($activeLevels !== [] && !isset($activeLevelSet[$level])) {
        continue;
    }
    if ($searchLower !== '') {
        $haystack = mb_strtolower($formatMessage($entry['message'] ?? '') . ' ' . $level);
        if (!str_contains($haystack, $searchLower)) {
            continue;
        }
    }
    $filteredLogs[] = $entry;
}

$presentLevels = array_values(array_filter($severityOrder, static fn(string $l): bool => isset($counts[$l])));
?>

<style>
.ssr-log {
    --ssr-divider: #e0e0e2;
    --ssr-hover: #f3f4f6;
    --ssr-mono: var(--font-mono);
}
.ssr-log-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.ssr-log-toolbar h2 { font-size: 14px; font-weight: 600; color: var(--color-text-secondary); margin: 0; }
.ssr-log-form { display: flex; gap: 8px; align-items: stretch; }
.ssr-log-form input[type="text"] { padding: 6px 10px; font-size: 13px; border: 1px solid var(--color-border); border-radius: 6px; min-width: 220px; }
.ssr-log-form button { padding: 6px 14px; font-size: 13px; }
.ssr-log-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
.ssr-log-chip { display: inline-flex; align-items: center; height: 24px; padding: 0 10px; border-radius: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase; text-decoration: none; border: 1px solid; }
.ssr-log-chip--clear { color: var(--color-text-secondary); border-color: var(--color-border); background: transparent; text-transform: none; letter-spacing: 0; }
.ssr-log-list { border: 1px solid var(--ssr-divider); border-radius: var(--radius); overflow: hidden; background: var(--color-surface); }
.ssr-log-row { border-bottom: 1px solid var(--ssr-divider); }
.ssr-log-row:last-child { border-bottom: none; }
.ssr-log-row > summary { display: flex; align-items: flex-start; gap: 12px; padding: 8px 12px; cursor: pointer; list-style: none; transition: background 0.1s ease; }
.ssr-log-row > summary::-webkit-details-marker { display: none; }
.ssr-log-row > summary:hover, .ssr-log-row[open] > summary { background: var(--ssr-hover); }
.ssr-log-time { font-family: var(--ssr-mono); font-size: 11px; flex-shrink: 0; width: 110px; padding-top: 2px; color: var(--color-text-secondary); }
.ssr-log-level { display: inline-flex; align-items: center; justify-content: center; min-width: 64px; height: 20px; padding: 0 8px; border-radius: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.4px; color: #fff; flex-shrink: 0; margin-top: 1px; }
.ssr-log-message { flex: 1; font-size: 13px; word-break: break-word; line-height: 1.5; }
.ssr-log-caret { flex-shrink: 0; color: var(--color-text-secondary); font-size: 12px; padding-top: 3px; transition: transform 0.15s ease; }
.ssr-log-row[open] .ssr-log-caret { transform: rotate(180deg); }
.ssr-log-detail { padding: 12px 12px 16px 134px; background: var(--ssr-hover); border-top: 1px solid var(--ssr-divider); font-size: 12px; }
.ssr-log-detail-line { font-family: var(--ssr-mono); color: var(--color-primary); margin-bottom: 8px; word-break: break-all; }
.ssr-log-detail pre { background: var(--color-code-bg); color: var(--color-code-text); padding: 12px; border-radius: 6px; font-family: var(--ssr-mono); font-size: 12px; line-height: 1.5; overflow-x: auto; margin: 0; }
.ssr-log-empty { padding: 32px; text-align: center; color: var(--color-text-secondary); font-size: 14px; }
.ssr-log-entry-picker { display: flex; gap: 8px; align-items: center; font-size: 12px; color: var(--color-text-secondary); margin-bottom: 16px; }
.ssr-log-entry-picker select { padding: 6px 10px; font-size: 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-surface); font-family: var(--ssr-mono); max-width: 100%; }
@media (max-width: 640px) {
    .ssr-log-time { width: 80px; font-size: 10px; }
    .ssr-log-level { min-width: 52px; font-size: 9px; }
    .ssr-log-detail { padding-left: 16px; }
}
</style>

<div class="page-header">
    <h1>SSR Log Panel</h1>
    <p>
        Server-rendered demo of the LogCollector panel — the same data as the React panel,
        rendered as plain HTML on the backend. Filtering uses query parameters; row expansion
        uses native <code>&lt;details&gt;</code> elements (no JS).
    </p>
</div>

<div class="card ssr-log">

<?php if ($currentSummary === null): ?>
    <div class="alert alert-info">
        No debug entries are stored yet. Visit the
        <a href="<?= Html::encode($urlGenerator->generate('log-demo')) ?>">Log Demo</a>
        page (or any other page) to generate one, then come back.
    </div>
<?php elseif ($errorMessage !== null): ?>
    <div class="alert alert-error">
        <?= Html::encode($errorMessage) ?>
    </div>
<?php else: ?>

    <form method="get" action="<?= Html::encode($urlGenerator->generate('ssr-log-panel')) ?>" class="ssr-log-entry-picker">
        <label for="ssr-entry">Debug entry:</label>
        <select id="ssr-entry" name="id" onchange="this.form.submit()">
            <?php foreach ($entries as $entry):
                $entryId = (string) ($entry['id'] ?? '');
                $request = $entry['web']['request'] ?? null;
                $method = is_array($request) ? (string) ($request['method'] ?? '') : '';
                $path = '';
                if (is_array($request)) {
                    $url = (string) ($request['url'] ?? ($request['path'] ?? ''));
                    $path = parse_url($url, PHP_URL_PATH) ?: $url;
                }
                $command = is_array($entry['console']['command'] ?? null) ? (string) ($entry['console']['command']['name'] ?? '') : '';
                $label = trim(($method !== '' ? $method . ' ' : '') . ($path !== '' ? $path : $command));
                $label = $label === '' ? $entryId : $label . ' — ' . substr($entryId, 0, 8);
            ?>
                <option value="<?= Html::encode($entryId) ?>"<?= $entryId === ($currentSummary['id'] ?? '') ? ' selected' : '' ?>>
                    <?= Html::encode($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($searchTerm !== ''): ?>
            <input type="hidden" name="q" value="<?= Html::encode($searchTerm) ?>">
        <?php endif; ?>
        <?php if ($activeLevels !== []): ?>
            <input type="hidden" name="level" value="<?= Html::encode(implode(',', $activeLevels)) ?>">
        <?php endif; ?>
    </form>

    <div class="ssr-log-toolbar">
        <h2><?= count($filteredLogs) ?> log <?= count($filteredLogs) === 1 ? 'entry' : 'entries' ?></h2>
        <form method="get" action="<?= Html::encode($urlGenerator->generate('ssr-log-panel')) ?>" class="ssr-log-form">
            <input type="hidden" name="id" value="<?= Html::encode((string) ($currentSummary['id'] ?? '')) ?>">
            <?php if ($activeLevels !== []): ?>
                <input type="hidden" name="level" value="<?= Html::encode(implode(',', $activeLevels)) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= Html::encode($searchTerm) ?>" placeholder="Filter logs...">
            <button type="submit" class="btn btn-outline">Search</button>
        </form>
    </div>

    <?php if (count($presentLevels) > 1): ?>
        <div class="ssr-log-chips">
            <?php foreach ($presentLevels as $level):
                $isActive = isset($activeLevelSet[$level]);
                $color = $levelColor($level);
                $nextLevels = $isActive
                    ? array_values(array_filter($activeLevels, static fn(string $l): bool => $l !== $level))
                    : array_merge($activeLevels, [$level]);
                $nextParam = $nextLevels === [] ? null : implode(',', $nextLevels);
                $url = $buildUrl(['level' => $nextParam]);
                $style = $isActive
                    ? sprintf('background: %s; color: #fff; border-color: %s;', $color, $color)
                    : sprintf('color: %s; border-color: %s; background: transparent;', $color, $color);
            ?>
                <a href="<?= Html::encode($url) ?>" class="ssr-log-chip" style="<?= Html::encode($style) ?>">
                    <?= Html::encode(strtoupper($level)) ?> (<?= (int) $counts[$level] ?>)
                </a>
            <?php endforeach; ?>
            <?php if ($activeLevels !== []): ?>
                <a href="<?= Html::encode($buildUrl(['level' => null])) ?>" class="ssr-log-chip ssr-log-chip--clear">Clear</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($filteredLogs === []): ?>
        <div class="ssr-log-empty">
            <?= $logs === [] ? 'No logs were collected for this entry.' : 'No logs match the current filter.' ?>
        </div>
    <?php else: ?>
        <div class="ssr-log-list">
            <?php foreach ($filteredLogs as $entry):
                $level = (string) ($entry['level'] ?? 'debug');
                $color = $levelColor($level);
                $time = (float) ($entry['time'] ?? 0);
                $line = (string) ($entry['line'] ?? '');
                $context = $entry['context'] ?? null;
                $hasDetails = $line !== '' || (is_array($context) && $context !== []);
            ?>
                <details class="ssr-log-row">
                    <summary>
                        <span class="ssr-log-time"><?= Html::encode($formatMicrotime($time)) ?></span>
                        <span class="ssr-log-level" style="background: <?= Html::encode($color) ?>;">
                            <?= Html::encode(strtoupper($level)) ?>
                        </span>
                        <span class="ssr-log-message"><?= Html::encode($formatMessage($entry['message'] ?? '')) ?></span>
                        <span class="ssr-log-caret" aria-hidden="true"><?= $hasDetails ? '▾' : '·' ?></span>
                    </summary>
                    <?php if ($hasDetails): ?>
                        <div class="ssr-log-detail">
                            <?php if ($line !== ''): ?>
                                <div class="ssr-log-detail-line"><?= Html::encode($line) ?></div>
                            <?php endif; ?>
                            <?php if (is_array($context) && $context !== []): ?>
                                <pre><?= Html::encode(
                                    json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
                                ) ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

</div>
