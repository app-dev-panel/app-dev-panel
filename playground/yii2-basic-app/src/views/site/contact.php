<?php

/**
 * @var yii\web\View $this
 * @var array<string, string> $errors
 * @var bool $success
 * @var array{name?: string, email?: string, message?: string} $data
 */
$this->title = 'Contact';
?>
<div class="page-header">
    <h1>Contact</h1>
    <p>Submit the form to generate POST request data for ADP inspection.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">Your message has been sent successfully. Check ADP for request details.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">Please fix the errors below and try again.</div>
<?php endif; ?>

<div class="card">
    <form method="post" action="/contact">
        <input type="hidden" name="<?= htmlspecialchars(\Yii::$app->request->csrfParam) ?>" value="<?= htmlspecialchars(\Yii::$app->request->csrfToken) ?>">

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control<?= isset($errors['name'])
                ? ' error'
                : '' ?>" value="<?= htmlspecialchars($data['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control<?= isset($errors['email'])
                ? ' error'
                : '' ?>" value="<?= htmlspecialchars($data['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" class="form-control<?= isset($errors['message'])
                ? ' error'
                : '' ?>"><?= htmlspecialchars($data['message'] ?? '') ?></textarea>
            <?php if (isset($errors['message'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['message']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>
