<?php

declare(strict_types=1);

use App\Auth\DemoIdentity;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var DemoIdentity|null $identity
 * @var bool $isGuest
 * @var string|null $userId
 * @var list<string> $roles
 * @var array<string, bool> $permissions
 */

$this->setTitle('Authorization Demo');

$tokens = [
    \App\Auth\DemoIdentityRepository::demoToken('alice') => 'Alice (admin)',
    \App\Auth\DemoIdentityRepository::demoToken('bob') => 'Bob (editor)',
    \App\Auth\DemoIdentityRepository::demoToken('carol') => 'Carol (reader)',
];
?>

<div class="page-header">
    <h1>Authorization Demo</h1>
    <p>
        Switches the current user by passing <code>?token=</code> in the URL. The ADP
        <a href="/inspect/#/authorization">Authorization Inspector</a> reads the same RBAC
        storage and identity components this page is using.
    </p>
</div>

<div class="grid grid-2">
    <?php foreach ($tokens as $tokenValue => $label): ?>
        <a href="<?= $urlGenerator->generate('authorization') ?>?token=<?= Html::encode($tokenValue) ?>"
           class="feature-card">
            <div>
                <h3><?= Html::encode($label) ?></h3>
                <p>token=<code><?= Html::encode($tokenValue) ?></code></p>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<h2 style="margin-top: 2rem;">Current request</h2>
<ul>
    <li><strong>Guest:</strong> <?= $isGuest ? 'yes' : 'no' ?></li>
    <li><strong>User ID:</strong> <?= $userId === null ? '—' : Html::encode($userId) ?></li>
    <?php if ($identity !== null): ?>
        <?php foreach ($identity->getAttributes() as $key => $value): ?>
            <li><strong><?= Html::encode($key) ?>:</strong> <?= Html::encode((string) $value) ?></li>
        <?php endforeach; ?>
    <?php endif; ?>
    <li><strong>Roles:</strong>
        <?= $roles === [] ? '—' : Html::encode(implode(', ', $roles)) ?></li>
</ul>

<h2>Permission checks</h2>
<ul>
    <?php foreach ($permissions as $permission => $granted): ?>
        <li>
            <code><?= Html::encode($permission) ?></code>:
            <strong style="color: <?= $granted ? '#2e7d32' : '#c62828' ?>">
                <?= $granted ? 'granted' : 'denied' ?>
            </strong>
        </li>
    <?php endforeach; ?>
</ul>
