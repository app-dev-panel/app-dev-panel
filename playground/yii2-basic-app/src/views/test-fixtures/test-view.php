<?php

/**
 * @var string $title
 * @var string[] $items
 */
?>
<div class="test-view">
    <h1><?= htmlspecialchars($title) ?></h1>
    <ul>
        <?php foreach ($items as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
