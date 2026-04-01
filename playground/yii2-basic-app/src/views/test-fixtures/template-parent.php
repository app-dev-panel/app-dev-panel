<?php

/**
 * @var yii\base\View $this
 * @var string $section
 */
?>
<div class="template-parent">
    <h1>Parent: <?= htmlspecialchars($section) ?></h1>
    <?= $this->render('template-child', ['label' => 'nested-item']) ?>
</div>
