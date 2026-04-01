<?php

/** @var yii\web\View $this */
$this->title = 'API Playground';
?>
<div class="page-header">
    <h1>API Playground</h1>
    <p>Send requests to the built-in API and inspect them in ADP.</p>
</div>

<div class="card">
    <div class="card-title">Request</div>

    <div class="api-playground-row" style="margin-bottom: 16px;">
        <select id="method" class="form-control" style="width: auto; min-width: 100px;">
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="DELETE">DELETE</option>
        </select>
        <select id="endpoint" class="form-control" style="flex: 1;">
            <option value="/api">/api — API Index</option>
            <option value="/api/users">/api/users — List Users</option>
            <option value="/api/error">/api/error — Trigger Error</option>
        </select>
        <button id="send-btn" class="btn btn-primary">Send Request</button>
    </div>

    <div id="body-group" class="form-group" style="display: none;">
        <label for="body">Request Body (JSON)</label>
        <textarea id="body" class="form-control" placeholder='{"key": "value"}'></textarea>
    </div>
</div>

<div class="card">
    <div class="card-title">Response</div>
    <div id="response-status" class="response-status"></div>
    <pre id="response-body" class="code-block" style="min-height: 80px;">Send a request to see the response here.</pre>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const methodSelect = document.getElementById('method');
    const endpointSelect = document.getElementById('endpoint');
    const bodyGroup = document.getElementById('body-group');
    const bodyInput = document.getElementById('body');
    const sendBtn = document.getElementById('send-btn');
    const responseStatus = document.getElementById('response-status');
    const responseBody = document.getElementById('response-body');

    methodSelect.addEventListener('change', function() {
        bodyGroup.style.display = this.value === 'GET' ? 'none' : 'block';
    });

    sendBtn.addEventListener('click', async function() {
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
        responseStatus.textContent = '';
        responseBody.textContent = '';

        try {
            const options = { method: methodSelect.value, headers: {} };
            if (methodSelect.value !== 'GET' && bodyInput.value.trim()) {
                options.headers['Content-Type'] = 'application/json';
                options.body = bodyInput.value;
            }
            const response = await fetch(endpointSelect.value, options);
            const text = await response.text();

            responseStatus.className = 'response-status ' + (response.ok ? 'ok' : 'error');
            responseStatus.textContent = response.status + ' ' + response.statusText;

            try {
                responseBody.textContent = JSON.stringify(JSON.parse(text), null, 2);
            } catch {
                responseBody.textContent = text;
            }
        } catch (err) {
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
