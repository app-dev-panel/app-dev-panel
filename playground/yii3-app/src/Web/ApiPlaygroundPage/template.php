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
        <select id="method">
            <option value="GET">GET</option>
            <option value="POST">POST</option>
        </select>
        <select id="endpoint">
            <option value="/api">/api</option>
            <option value="/api/users">/api/users</option>
            <option value="/api/error">/api/error</option>
        </select>
        <button class="btn btn-primary" id="send-btn">Send Request</button>
    </div>
    <div class="form-group" id="body-group" style="display: none; margin-top: 16px;">
        <label for="body">Request Body (JSON)</label>
        <textarea id="body" class="form-control" placeholder='{"key": "value"}'></textarea>
    </div>
</div>

<div class="card" id="api-response-card" style="display: none; margin-top: 16px;">
    <div class="card-title">Response</div>
    <div id="response-status" class="response-status"></div>
    <pre class="code-block" id="response-body"></pre>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const methodSelect = document.getElementById('method');
    const endpointSelect = document.getElementById('endpoint');
    const sendBtn = document.getElementById('send-btn');
    const bodyGroup = document.getElementById('body-group');
    const bodyTextarea = document.getElementById('body');
    const responseCard = document.getElementById('api-response-card');
    const responseStatus = document.getElementById('response-status');
    const responseBody = document.getElementById('response-body');

    methodSelect.addEventListener('change', function () {
        bodyGroup.style.display = methodSelect.value === 'POST' ? 'block' : 'none';
    });

    sendBtn.addEventListener('click', async function () {
        const method = methodSelect.value;
        const url = endpointSelect.value;
        sendBtn.disabled = true;
        sendBtn.textContent = 'Loading...';

        try {
            const options = {
                method: method,
                headers: { 'Accept': 'application/json' }
            };

            if (method === 'POST') {
                options.headers['Content-Type'] = 'application/json';
                options.body = bodyTextarea.value || '{}';
            }

            const response = await fetch(url, options);
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
});
</script>
