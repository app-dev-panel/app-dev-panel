<?php

/** @var yii\web\View $this */
$this->title = 'Home';
?>
<div class="page-header">
    <h1>ADP Yii 2 Playground</h1>
    <p>A demo application for testing Application Development Panel with Yii 2 framework.</p>
</div>

<div class="grid grid-2">
    <a href="/users" class="feature-card">
        <h3>Users</h3>
        <p>Browse a list of demo users displayed in a table. Generates log entries and events for ADP inspection.</p>
    </a>
    <a href="/contact" class="feature-card">
        <h3>Contact Form</h3>
        <p>Submit a contact form with validation. Demonstrates POST requests, form handling, and logging.</p>
    </a>
    <a href="/api-playground" class="feature-card">
        <h3>API Playground</h3>
        <p>Send requests to the built-in JSON API endpoints. Inspect requests and responses in ADP.</p>
    </a>
    <a href="/error" class="feature-card">
        <h3>Error Demo</h3>
        <p>Trigger a runtime exception to see how ADP captures and displays error details.</p>
    </a>
</div>
