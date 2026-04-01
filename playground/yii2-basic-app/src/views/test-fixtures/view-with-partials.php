<?php

/** @var string $title */
?>
<div class="view-with-partials">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?= (string) $this->render('_sidebar', ['items' => ['Home', 'About', 'Contact']]) ?>
    <?= (string) $this->render('_content-block', ['text' => 'Hello from sub-view']) ?>
</div>
