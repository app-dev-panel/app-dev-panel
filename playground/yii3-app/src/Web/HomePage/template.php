<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('ADP Yii 3 Playground');
?>

<div class="page-header">
    <h1>ADP Yii 3 Playground</h1>
    <p>A demo application for testing the Application Development Panel</p>
</div>

<div class="grid grid-2">
    <a href="<?= $urlGenerator->generate('users') ?>" class="feature-card">
        <div>
            <h3>Users</h3>
            <p>Browse the user directory with server-rendered table.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('contact') ?>" class="feature-card">
        <div>
            <h3>Contact Form</h3>
            <p>Submit a form with server-side validation.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('api-playground') ?>" class="feature-card">
        <div>
            <h3>API Playground</h3>
            <p>Send requests to API endpoints and inspect responses.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('error-demo') ?>" class="feature-card">
        <div>
            <h3>Error Demo</h3>
            <p>Trigger an exception to test the error collector.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('log-demo') ?>" class="feature-card">
        <div>
            <h3>Log Demo</h3>
            <p>Send log messages with different severity levels and context data.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('var-dumper') ?>" class="feature-card">
        <div>
            <h3>Var Dumper</h3>
            <p>Dump variables to inspect their structure in ADP.</p>
        </div>
    </a>

    <a href="<?= $urlGenerator->generate('authorization') ?>" class="feature-card">
        <div>
            <h3>Authorization</h3>
            <p>Switch users via token and probe RBAC roles and permissions.</p>
        </div>
    </a>

    <a href="/debug/" class="feature-card">
        <div>
            <h3>Debug Panel</h3>
            <p>View collected debug data in the ADP panel.</p>
        </div>
    </a>
</div>
