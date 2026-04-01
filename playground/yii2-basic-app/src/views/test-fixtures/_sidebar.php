<?php

/**
 * Sidebar partial.
 *
 * @var string[] $items
 */
?>
<nav class="sidebar">
    <ul>
        <?php foreach ($items as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
</nav>
