<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('API Playground');
?>

<div class="page-header">
    <h1>API Playground</h1>
    <p>Test the JSON API endpoints. Each request generates debug data captured by ADP.</p>
</div>

<div class="card">
    <div class="card-title">Try an Endpoint</div>
    <div class="api-playground-row">
        <select id="api-endpoint">
            <option value="/api/">GET /api/ &mdash; Index</option>
            <option value="/api/users">GET /api/users &mdash; List Users</option>
            <option value="/api/error">GET /api/error &mdash; Trigger Error</option>
        </select>
        <button class="btn btn-primary" id="api-send">Send Request</button>
    </div>
</div>

<div class="card" id="api-response-card" style="display: none; margin-top: 16px;">
    <div class="card-title">Response</div>
    <div id="api-response-status" class="response-status"></div>
    <pre class="code-block" id="api-response-body"></pre>
</div>

<script>
(function () {
    const sendBtn = document.getElementById('api-send');
    const endpointSelect = document.getElementById('api-endpoint');
    const responseCard = document.getElementById('api-response-card');
    const responseStatus = document.getElementById('api-response-status');
    const responseBody = document.getElementById('api-response-body');

    sendBtn.addEventListener('click', async function () {
        const url = endpointSelect.value;
        sendBtn.disabled = true;
        sendBtn.textContent = 'Loading...';

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            const text = await response.text();
            let formatted;
            try {
                formatted = JSON.stringify(JSON.parse(text), null, 2);
            } catch (e) {
                formatted = text;
            }

            responseCard.style.display = 'block';
            responseStatus.className = 'response-status ' + (response.ok ? 'ok' : 'error');
            responseStatus.textContent = response.status + ' ' + response.statusText;
            responseBody.textContent = formatted;
        } catch (err) {
            responseCard.style.display = 'block';
            responseStatus.className = 'response-status error';
            responseStatus.textContent = 'Network Error';
            responseBody.textContent = err.message;
        } finally {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send Request';
        }
    });
})();
</script>
