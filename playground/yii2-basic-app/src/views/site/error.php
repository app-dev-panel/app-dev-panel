<?php

/**
 * @var yii\web\View $this
 * @var \Throwable|null $exception
 */
$this->title = 'Error';
?>
<div class="page-header">
    <h1>Error</h1>
    <p>Something went wrong. The details are captured by ADP for inspection.</p>
</div>

<div class="card">
    <div class="card-title">Exception Details</div>
    <?php if ($exception !== null): ?>
        <div class="alert alert-error" style="margin-bottom: 12px;">
            <?= htmlspecialchars($exception->getMessage()) ?>
        </div>
        <div style="font-size: 13px; color: var(--color-text-secondary);">
            <strong>Type:</strong> <?= htmlspecialchars($exception::class) ?><br>
            <strong>Code:</strong> <?= $exception->getCode() ?><br>
            <strong>File:</strong> <?= htmlspecialchars($exception->getFile()) ?>:<?= $exception->getLine() ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No exception details available. <a href="/error">Trigger an error</a> to see it in action.</div>
    <?php endif; ?>
</div>
