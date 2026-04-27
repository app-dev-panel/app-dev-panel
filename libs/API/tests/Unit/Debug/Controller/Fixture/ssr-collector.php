<?php

declare(strict_types=1);

/**
 * Fixture template used by {@see \AppDevPanel\Api\Tests\Unit\Debug\Controller\DebugControllerTest}.
 *
 * @var array<string, mixed> $data Collector data for the entry being viewed.
 */

$logs = is_array($data['logs'] ?? null) ? $data['logs'] : [];
?>
<div data-marker="SSR-LOGS-1" class="ssr-collector-stub">
    <ul>
        <?php foreach ($logs as $entry): ?>
            <li>
                <strong><?= htmlspecialchars((string) ($entry['level'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>:
                <?= htmlspecialchars((string) ($entry['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
