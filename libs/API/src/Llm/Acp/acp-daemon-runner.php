#!/usr/bin/env php
<?php

/**
 * Self-contained ACP daemon — persistent ACP agent process with Unix socket server.
 *
 * Spawns an ACP agent subprocess once, performs the initialize handshake,
 * then listens on a Unix socket for prompt requests from PHP web processes.
 *
 * Protocol (over Unix socket, newline-delimited JSON):
 *   Request:  {"action": "prompt", "messages": [...], "customPrompt": "...", "timeout": 60}
 *   Response: {"text": "...", "stopReason": "...", "agentName": "...", "agentVersion": "...", "toolCalls": [...]}
 *
 *   Request:  {"action": "ping"}
 *   Response: {"ok": true}
 *
 *   Request:  {"action": "shutdown"}
 *   Response: {"ok": true}  (then daemon exits)
 *
 * Usage:
 *   php acp-daemon-runner.php \
 *     --socket=/path/to/.acp-daemon.sock \
 *     --pid=/path/to/.acp-daemon.pid \
 *     --command=npx \
 *     --args='["@agentclientprotocol/claude-agent-acp"]' \
 *     --env='{}'
 *
 * No Composer autoloader needed — all logic is self-contained.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Parse CLI arguments
// ---------------------------------------------------------------------------

$options = getopt('', ['socket:', 'pid:', 'command:', 'args::', 'env::']);

$socketPath = $options['socket'] ?? '';
$pidFile = $options['pid'] ?? '';
$agentCommand = $options['command'] ?? '';
$agentArgs = json_decode($options['args'] ?? '[]', true) ?: [];
$agentEnv = json_decode($options['env'] ?? '{}', true) ?: [];

if ($socketPath === '' || $pidFile === '' || $agentCommand === '') {
    fwrite(
        STDERR,
        "Usage: php acp-daemon-runner.php --socket=PATH --pid=PATH --command=CMD [--args=JSON] [--env=JSON]\n",
    );
    exit(1);
}

// ---------------------------------------------------------------------------
// Daemonize: write PID, set up signal handling
// ---------------------------------------------------------------------------

file_put_contents($pidFile, (string) getmypid());

$running = true;

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, static function () use (&$running): void {
        $running = false;
    });
    pcntl_signal(SIGINT, static function () use (&$running): void {
        $running = false;
    });
}

// ---------------------------------------------------------------------------
// Spawn ACP agent subprocess
// ---------------------------------------------------------------------------

$fullCommand = array_merge([$agentCommand], $agentArgs);
$commandLine = implode(' ', array_map('escapeshellarg', $fullCommand));

$descriptors = [
    0 => ['pipe', 'r'], // stdin — we write
    1 => ['pipe', 'w'], // stdout — we read
    2 => ['pipe', 'w'], // stderr — diagnostics
];

$mergedEnv = array_merge(getenv() ?: [], $agentEnv);
$cwd = getcwd() ?: sys_get_temp_dir();

$process = proc_open($commandLine, $descriptors, $pipes, $cwd, $mergedEnv);

if (!is_resource($process)) {
    fwrite(STDERR, "Failed to spawn ACP agent: {$commandLine}\n");
    @unlink($pidFile);
    exit(1);
}

$stdin = $pipes[0] ?? null;
$stdout = $pipes[1] ?? null;
$stderr = $pipes[2] ?? null;

if ($stdin === null || $stdout === null || $stderr === null) {
    fwrite(STDERR, "Failed to open stdio pipes for ACP agent.\n");
    proc_close($process);
    @unlink($pidFile);
    exit(1);
}

stream_set_blocking($stderr, false);

// JSON-RPC request counter
$nextId = 1;

// ---------------------------------------------------------------------------
// Helper: send JSON-RPC message to agent stdin
// ---------------------------------------------------------------------------

function agentSend(mixed $stdin, array $message): void
{
    $json = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    fwrite($stdin, $json . "\n");
    fflush($stdin);
}

// ---------------------------------------------------------------------------
// Helper: receive JSON-RPC message from agent stdout (with timeout)
// ---------------------------------------------------------------------------

function agentReceive(mixed $stdout, float $timeoutSeconds): ?array
{
    $deadline = microtime(true) + $timeoutSeconds;

    while (microtime(true) < $deadline) {
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            break;
        }

        $read = [$stdout];
        $write = null;
        $except = null;
        $seconds = (int) $remaining;
        $microseconds = (int) (($remaining - $seconds) * 1_000_000);

        $ready = @stream_select($read, $write, $except, $seconds, $microseconds);

        if ($ready === false || $ready === 0) {
            continue;
        }

        $line = fgets($stdout);

        if ($line === false) {
            return null;
        }

        $line = trim($line);
        if ($line === '') {
            continue;
        }

        return json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    }

    return null;
}

// ---------------------------------------------------------------------------
// Helper: wait for a JSON-RPC response (skip notifications)
// ---------------------------------------------------------------------------

function waitForResponse(mixed $stdout, float $timeout, string $errorPrefix): array
{
    $deadline = microtime(true) + $timeout;

    while (microtime(true) < $deadline) {
        $message = agentReceive($stdout, min($deadline - microtime(true), 5.0));

        if ($message === null) {
            continue;
        }

        // Skip notifications (no id)
        if (!isset($message['id'])) {
            continue;
        }

        if (isset($message['error'])) {
            throw new RuntimeException(sprintf(
                '%s: %s',
                $errorPrefix,
                $message['error']['message'] ?? 'Unknown error',
            ));
        }

        return $message;
    }

    throw new RuntimeException("Timeout waiting for ACP agent response ({$errorPrefix}).");
}

// ---------------------------------------------------------------------------
// Helper: check if agent process is alive
// ---------------------------------------------------------------------------

function isAgentAlive(mixed $process): bool
{
    if (!is_resource($process)) {
        return false;
    }
    $status = proc_get_status($process);
    return $status['running'] === true;
}

// ---------------------------------------------------------------------------
// Helper: read stderr (non-blocking)
// ---------------------------------------------------------------------------

function readStderr(mixed $stderr): string
{
    $output = '';
    while (($chunk = @fread($stderr, 8192)) !== false && $chunk !== '') {
        $output .= $chunk;
    }
    return $output;
}

// ---------------------------------------------------------------------------
// Step 1: Initialize ACP handshake
// ---------------------------------------------------------------------------

fwrite(STDERR, "ACP daemon: initializing agent...\n");

agentSend($stdin, [
    'jsonrpc' => '2.0',
    'id' => $nextId++,
    'method' => 'initialize',
    'params' => [
        'protocolVersion' => 1,
        'capabilities' => (object) [],
        'clientInfo' => [
            'name' => 'ADP',
            'version' => '1.0.0',
        ],
    ],
]);

try {
    $initResponse = waitForResponse($stdout, 30.0, 'ACP initialize failed');
} catch (RuntimeException $e) {
    fwrite(STDERR, "ACP daemon: {$e->getMessage()}\n");
    proc_terminate($process);
    proc_close($process);
    @unlink($pidFile);
    exit(1);
}

$agentName = $initResponse['result']['agentInfo']['name'] ?? '';
$agentVersion = $initResponse['result']['agentInfo']['version'] ?? '';

fwrite(STDERR, "ACP daemon: agent initialized — {$agentName} {$agentVersion}\n");

// ---------------------------------------------------------------------------
// Step 2: Create Unix socket server
// ---------------------------------------------------------------------------

// Clean up stale socket file
if (file_exists($socketPath)) {
    @unlink($socketPath);
}

$server = stream_socket_server("unix://{$socketPath}", $errno, $errstr);

if ($server === false) {
    fwrite(STDERR, "ACP daemon: failed to create socket: {$errstr}\n");
    proc_terminate($process);
    proc_close($process);
    @unlink($pidFile);
    exit(1);
}

// Make socket accessible
chmod($socketPath, 0660);

fwrite(STDERR, "ACP daemon: listening on {$socketPath}\n");

// ---------------------------------------------------------------------------
// Step 3: Main loop — accept connections, handle requests
// ---------------------------------------------------------------------------

while ($running) {
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }

    if (!$running) {
        break;
    }

    // Check agent health
    if (!isAgentAlive($process)) {
        $stderrOutput = readStderr($stderr);
        fwrite(STDERR, "ACP daemon: agent process died unexpectedly. stderr: {$stderrOutput}\n");
        break;
    }

    // Accept connection with timeout (1 second) to allow signal handling
    $read = [$server];
    $write = null;
    $except = null;
    $ready = @stream_select($read, $write, $except, 1, 0);

    if ($ready === false || $ready === 0) {
        continue;
    }

    $client = @stream_socket_accept($server, 1.0);

    if ($client === false) {
        continue;
    }

    // Read request (single JSON line)
    $requestLine = fgets($client);

    if ($requestLine === false) {
        fclose($client);
        continue;
    }

    $request = json_decode(trim($requestLine), true);

    if (!is_array($request)) {
        fwrite($client, json_encode(['error' => 'Invalid JSON request']) . "\n");
        fclose($client);
        continue;
    }

    $action = $request['action'] ?? '';

    // --- Ping ---
    if ($action === 'ping') {
        fwrite($client, json_encode(['ok' => true, 'agentName' => $agentName, 'agentVersion' => $agentVersion]) . "\n");
        fclose($client);
        continue;
    }

    // --- Shutdown ---
    if ($action === 'shutdown') {
        fwrite($client, json_encode(['ok' => true]) . "\n");
        fclose($client);
        $running = false;
        break;
    }

    // --- Prompt ---
    if ($action === 'prompt') {
        $response = handlePrompt(
            $request,
            $stdin,
            $stdout,
            $process,
            $stderr,
            $nextId,
            $agentName,
            $agentVersion,
            $cwd,
        );
        fwrite($client, json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        fclose($client);
        continue;
    }

    fwrite($client, json_encode(['error' => "Unknown action: {$action}"]) . "\n");
    fclose($client);
}

// ---------------------------------------------------------------------------
// Cleanup
// ---------------------------------------------------------------------------

fwrite(STDERR, "ACP daemon: shutting down...\n");

fclose($server);
@unlink($socketPath);

if (is_resource($stdin)) {
    fclose($stdin);
}
if (is_resource($stdout)) {
    fclose($stdout);
}
if (is_resource($stderr)) {
    fclose($stderr);
}

if (is_resource($process)) {
    if (isAgentAlive($process)) {
        proc_terminate($process);
    }
    proc_close($process);
}

@unlink($pidFile);
fwrite(STDERR, "ACP daemon: stopped.\n");
exit(0);

// ===========================================================================
// Prompt handler
// ===========================================================================

function handlePrompt(
    array $request,
    mixed $stdin,
    mixed $stdout,
    mixed $process,
    mixed $stderr,
    int &$nextId,
    string $agentName,
    string $agentVersion,
    string $cwd,
): array {
    $messages = $request['messages'] ?? [];
    $customPrompt = $request['customPrompt'] ?? '';
    $timeout = (float) ($request['timeout'] ?? 60);

    // --- Create session ---
    agentSend($stdin, [
        'jsonrpc' => '2.0',
        'id' => $nextId++,
        'method' => 'session/new',
        'params' => [
            'cwd' => $cwd,
            'mcpServers' => [],
        ],
    ]);

    try {
        $sessionResponse = waitForResponse($stdout, $timeout, 'session/new failed');
    } catch (RuntimeException $e) {
        return ['error' => $e->getMessage()];
    }

    $sessionId = $sessionResponse['result']['sessionId'] ?? null;

    if (!is_string($sessionId) || $sessionId === '') {
        return ['error' => 'ACP agent did not return a session ID.'];
    }

    // --- Build prompt content ---
    $parts = [];

    if ($customPrompt !== '') {
        $parts[] = "[Instructions: {$customPrompt}]";
    }

    foreach ($messages as $message) {
        if (!isset($message['role'], $message['content'])) {
            continue;
        }

        $role = (string) $message['role'];
        $content = (string) $message['content'];

        if ($role === 'system') {
            $parts[] = "[System: {$content}]";
        } elseif ($role === 'assistant') {
            $parts[] = "[Previous assistant response: {$content}]";
        } else {
            $parts[] = $content;
        }
    }

    $promptContent = [
        [
            'type' => 'text',
            'text' => implode("\n\n", $parts),
        ],
    ];

    // --- Send prompt ---
    $promptRequestId = $nextId++;

    agentSend($stdin, [
        'jsonrpc' => '2.0',
        'id' => $promptRequestId,
        'method' => 'session/prompt',
        'params' => [
            'sessionId' => $sessionId,
            'prompt' => $promptContent,
        ],
    ]);

    // --- Collect streaming response ---
    $textParts = [];
    $toolCalls = [];
    $rawMessages = [];
    $stopReason = 'end_turn';
    $totalTextSize = 0;
    $maxResponseSize = 10_000_000;
    $maxToolCalls = 1000;
    $deadline = microtime(true) + $timeout;

    while (microtime(true) < $deadline) {
        $remaining = $deadline - microtime(true);

        if ($remaining <= 0) {
            break;
        }

        $message = agentReceive($stdout, min($remaining, 5.0));

        if ($message === null) {
            if (!isAgentAlive($process)) {
                $stderrOutput = readStderr($stderr);
                return [
                    'error' => "ACP agent process terminated. stderr: {$stderrOutput}",
                    '_rawMessages' => $rawMessages,
                ];
            }
            continue;
        }

        $rawMessages[] = $message;

        // Notification (no id) — session/update
        if (!isset($message['id'])) {
            $method = $message['method'] ?? '';

            if ($method === 'session/update') {
                $update = $message['params']['update'] ?? [];
                $type = $update['type'] ?? '';

                if ($type === 'agent_message_chunk') {
                    $contentBlocks = $update['content'] ?? [];
                    foreach ($contentBlocks as $block) {
                        if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                            $text = (string) $block['text'];
                            $totalTextSize += strlen($text);
                            if ($totalTextSize > $maxResponseSize) {
                                return ['error' => 'ACP agent response exceeds maximum size limit.'];
                            }
                            $textParts[] = $text;
                        }
                    }
                }

                if ($type === 'tool_call_start' || $type === 'tool_call_update') {
                    if (count($toolCalls) < $maxToolCalls) {
                        $toolName = (string) ($update['toolCall']['name'] ?? $update['name'] ?? 'unknown');
                        $toolCalls[] = ['role' => 'tool', 'content' => $toolName];
                    }
                }
            }

            continue;
        }

        // Response to our prompt request
        if ($message['id'] === $promptRequestId) {
            if (isset($message['error'])) {
                return ['error' => sprintf(
                    'session/prompt failed: %s',
                    $message['error']['message'] ?? 'Unknown error',
                )];
            }

            $stopReason = $message['result']['stopReason'] ?? 'end_turn';
            break;
        }

        // Agent request to client — reject gracefully
        if (isset($message['method'])) {
            agentSend($stdin, [
                'jsonrpc' => '2.0',
                'id' => $message['id'],
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not supported by ADP ACP daemon.',
                ],
            ]);
        }
    }

    return [
        'text' => implode('', $textParts),
        'stopReason' => $stopReason,
        'agentName' => $agentName,
        'agentVersion' => $agentVersion,
        'toolCalls' => $toolCalls,
        '_rawMessages' => $rawMessages,
    ];
}
