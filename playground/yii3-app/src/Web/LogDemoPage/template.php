<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var bool $success
 * @var string $loggedLevel
 * @var string $loggedMessage
 */

$this->setTitle('Log Demo');
?>

<div class="page-header">
    <h1>Log Demo</h1>
    <p>Send log messages with different severity levels and context data. View them in ADP.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        Log message sent: <strong>[<?= Html::encode(strtoupper($loggedLevel)) ?>]</strong> <?= Html::encode(
            $loggedMessage,
        ) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Send a Log Message</div>
    <form method="POST">
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
        <div class="form-group">
            <label for="level">Severity Level</label>
            <select name="level" id="level" class="form-control" style="cursor: pointer;">
                <option value="debug">Debug</option>
                <option value="info" selected>Info</option>
                <option value="notice">Notice</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
                <option value="critical">Critical</option>
                <option value="alert">Alert</option>
                <option value="emergency">Emergency</option>
            </select>
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <input type="text" name="message" id="message" class="form-control"
                   value="User performed an action in the playground application">
        </div>
        <div class="form-group">
            <label for="context">Context (JSON)</label>
            <textarea name="context" id="context" class="form-control" rows="5">{
    "user_id": 42,
    "action": "playground_demo",
    "ip": "127.0.0.1",
    "session_id": "abc123def456"
}</textarea>
            <div class="form-hint">Optional JSON object passed as log context.</div>
        </div>
        <button type="submit" class="btn btn-primary">Send Log</button>
    </form>
</div>
