<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var string|null $csrf
 * @var array{name: string, email: string, message: string} $formData
 * @var array<string, string> $errors
 * @var bool $submitted
 */

$this->setTitle('Contact');
?>

<div class="page-header">
    <h1>Contact</h1>
    <p>A demo contact form. Submissions generate log entries and events captured by ADP.</p>
</div>

<?php if ($submitted): ?>
    <div class="alert alert-success">
        Thank you, <?= htmlspecialchars($formData['name']) ?>! Your message has been received. (This is a demo &mdash; no email was actually sent.)
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Send a Message</div>
    <form method="post" action="">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">

        <div class="form-group">
            <label for="contact-name">Name</label>
            <input
                type="text"
                id="contact-name"
                name="name"
                class="form-control<?= isset($errors['name']) ? ' error' : '' ?>"
                value="<?= htmlspecialchars($formData['name']) ?>"
                placeholder="Your name"
            >
            <?php if (isset($errors['name'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="contact-email">Email</label>
            <input
                type="email"
                id="contact-email"
                name="email"
                class="form-control<?= isset($errors['email']) ? ' error' : '' ?>"
                value="<?= htmlspecialchars($formData['email']) ?>"
                placeholder="you@example.com"
            >
            <?php if (isset($errors['email'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="contact-message">Message</label>
            <textarea
                id="contact-message"
                name="message"
                class="form-control<?= isset($errors['message']) ? ' error' : '' ?>"
                placeholder="Your message..."
            ><?= htmlspecialchars($formData['message']) ?></textarea>
            <?php if (isset($errors['message'])): ?>
                <div class="form-error"><?= htmlspecialchars($errors['message']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>
