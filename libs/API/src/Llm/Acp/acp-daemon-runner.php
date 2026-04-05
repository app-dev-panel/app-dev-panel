#!/usr/bin/env php
<?php

/**
 * Self-contained ACP daemon — manages multiple ACP agent subprocesses via Unix socket.
 *
 * Starts "empty" (no agents). Agents are spawned per session via `session-start`.
 * Each session has its own agent subprocess with independent ACP lifecycle.
 *
 * Protocol (over Unix socket, newline-delimited JSON):
 *
 *   {"action": "ping"}
 *   → {"ok": true, "sessions": 2}
 *
 *   {"action": "session-start", "sessionId": "uuid", "command": "npx", "args": [...], "env": {...}}
 *   → {"ok": true, "agentName": "...", "agentVersion": "..."}
 *
 *   {"action": "session-stop", "sessionId": "uuid"}
 *   → {"ok": true}
 *
 *   {"action": "session-status", "sessionId": "uuid"}
 *   → {"ok": true, "active": true, "agentName": "...", "agentVersion": "...", "busy": false}
 *
 *   {"action": "prompt", "sessionId": "uuid", "messages": [...], "customPrompt": "...", "timeout": 60}
 *   → {"text": "...", "stopReason": "...", "agentName": "...", "agentVersion": "...", "toolCalls": [...]}
 *
 *   {"action": "shutdown"}
 *   → {"ok": true}
 *
 * Usage:
 *   php acp-daemon-runner.php --socket=/tmp/adp-acp-xxx.sock --pid=/path/to/.acp-daemon.pid
 *
 * No Composer autoloader needed — all logic is self-contained.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Parse CLI arguments
// ---------------------------------------------------------------------------

$options = getopt('', ['socket:', 'pid:']);

$socketPath = $options['socket'] ?? '';
$pidFile = $options['pid'] ?? '';

if ($socketPath === '' || $pidFile === '') {
    fwrite(STDERR, "Usage: php acp-daemon-runner.php --socket=PATH --pid=PATH\n");
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
// Session registry
// ---------------------------------------------------------------------------

/**
 * @var array<string, array{
 *     process: resource,
 *     stdin: resource,
 *     stdout: resource,
 *     stderr: resource,
 *     nextId: int,
 *     agentName: string,
 *     agentVersion: string,
 *     lastActivity: float,
 *     busy: bool,
 * }> $sessions
 */
$sessions = [];

$maxSessions = 10;
$idleTimeoutSeconds = 1800.0; // 30 minutes

// ---------------------------------------------------------------------------
// JSON-RPC helpers (same as before, but parameterized by session pipes)
// ---------------------------------------------------------------------------

function agentSend(mixed $stdin, array $message): void
{
    $json = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    fwrite($stdin, $json . "\n");
    fflush($stdin);
}

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

function waitForResponse(mixed $stdout, float $timeout, string $errorPrefix): array
{
    $deadline = microtime(true) + $timeout;

    while (microtime(true) < $deadline) {
        $message = agentReceive($stdout, min($deadline - microtime(true), 5.0));

        if ($message === null) {
            continue;
        }

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

function isAgentAlive(mixed $process): bool
{
    if (!is_resource($process)) {
        return false;
    }
    $status = proc_get_status($process);
    return $status['running'] === true;
}

function readStderr(mixed $stderr): string
{
    $output = '';
    while (($chunk = @fread($stderr, 8192)) !== false && $chunk !== '') {
        $output .= $chunk;
    }
    return $output;
}

// ---------------------------------------------------------------------------
// Session management functions
// ---------------------------------------------------------------------------

/**
 * Spawn an ACP agent subprocess and perform the initialize handshake.
 *
 * @param list<string> $args
 * @param array<string, string> $env
 * @return array{process: resource, stdin: resource, stdout: resource, stderr: resource, nextId: int, agentName: string, agentVersion: string, lastActivity: float, busy: bool}
 */
function spawnAgent(string $command, array $args, array $env): array
{
    $fullCommand = array_merge([$command], $args);
    $commandLine = implode(' ', array_map('escapeshellarg', $fullCommand));

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $mergedEnv = array_merge(getenv() ?: [], $env);
    $cwd = getcwd() ?: sys_get_temp_dir();

    $process = proc_open($commandLine, $descriptors, $pipes, $cwd, $mergedEnv);

    if (!is_resource($process)) {
        throw new RuntimeException("Failed to spawn ACP agent: {$commandLine}");
    }

    $stdin = $pipes[0] ?? null;
    $stdout = $pipes[1] ?? null;
    $stderr = $pipes[2] ?? null;

    if ($stdin === null || $stdout === null || $stderr === null) {
        proc_close($process);
        throw new RuntimeException('Failed to open stdio pipes for ACP agent.');
    }

    stream_set_blocking($stderr, false);

    // Initialize handshake
    $nextId = 1;

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
        proc_terminate($process);
        proc_close($process);
        throw $e;
    }

    $agentName = $initResponse['result']['agentInfo']['name'] ?? '';
    $agentVersion = $initResponse['result']['agentInfo']['version'] ?? '';

    return [
        'process' => $process,
        'stdin' => $stdin,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'nextId' => $nextId,
        'agentName' => $agentName,
        'agentVersion' => $agentVersion,
        'lastActivity' => microtime(true),
        'busy' => false,
    ];
}

/**
 * Terminate an agent session and clean up resources.
 */
function terminateSession(array &$session): void
{
    if (is_resource($session['stdin'])) {
        fclose($session['stdin']);
    }
    if (is_resource($session['stdout'])) {
        fclose($session['stdout']);
    }
    if (is_resource($session['stderr'])) {
        fclose($session['stderr']);
    }
    if (is_resource($session['process'])) {
        if (isAgentAlive($session['process'])) {
            proc_terminate($session['process']);
        }
        proc_close($session['process']);
    }
}

/**
 * Handle a prompt request for a specific session.
 */
function handlePrompt(array $request, array &$session): array
{
    /** @var int $nextId */
    $nextId = $session['nextId'];

    $messages = $request['messages'] ?? [];
    $customPrompt = $request['customPrompt'] ?? '';
    $timeout = (float) ($request['timeout'] ?? 60);

    $stdin = $session['stdin'];
    $stdout = $session['stdout'];
    $process = $session['process'];
    $stderr = $session['stderr'];

    $cwd = getcwd() ?: sys_get_temp_dir();

    // --- Create ACP session ---
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

    $acpSessionId = $sessionResponse['result']['sessionId'] ?? null;

    if (!is_string($acpSessionId) || $acpSessionId === '') {
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
            'sessionId' => $acpSessionId,
            'prompt' => $promptContent,
        ],
    ]);

    // --- Collect streaming response ---
    $textParts = [];
    $toolCalls = [];
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

        $msg = agentReceive($stdout, min($remaining, 5.0));

        if ($msg === null) {
            if (!isAgentAlive($process)) {
                $stderrOutput = readStderr($stderr);
                return ['error' => "ACP agent process terminated. stderr: {$stderrOutput}"];
            }
            continue;
        }

        // Notification (no id) — session/update
        if (!isset($msg['id'])) {
            $method = $msg['method'] ?? '';

            if ($method === 'session/update') {
                $update = $msg['params']['update'] ?? [];
                $type = $update['sessionUpdate'] ?? '';

                if ($type === 'agent_message_chunk') {
                    $content = $update['content'] ?? [];
                    if (($content['type'] ?? '') === 'text' && isset($content['text'])) {
                        $text = (string) $content['text'];
                        $totalTextSize += strlen($text);
                        if ($totalTextSize > $maxResponseSize) {
                            return ['error' => 'ACP agent response exceeds maximum size limit.'];
                        }
                        $textParts[] = $text;
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
        if ($msg['id'] === $promptRequestId) {
            if (isset($msg['error'])) {
                return ['error' => sprintf('session/prompt failed: %s', $msg['error']['message'] ?? 'Unknown error')];
            }

            $stopReason = $msg['result']['stopReason'] ?? 'end_turn';
            break;
        }

        // Agent request to client — reject gracefully
        if (isset($msg['method'])) {
            agentSend($stdin, [
                'jsonrpc' => '2.0',
                'id' => $msg['id'],
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not supported by ADP ACP daemon.',
                ],
            ]);
        }
    }

    $session['nextId'] = $nextId;

    return [
        'text' => implode('', $textParts),
        'stopReason' => $stopReason,
        'agentName' => $session['agentName'],
        'agentVersion' => $session['agentVersion'],
        'toolCalls' => $toolCalls,
    ];
}

// ---------------------------------------------------------------------------
// Create Unix socket server
// ---------------------------------------------------------------------------

if (file_exists($socketPath)) {
    @unlink($socketPath);
}

$server = stream_socket_server("unix://{$socketPath}", $errno, $errstr);

if ($server === false) {
    fwrite(STDERR, "ACP daemon: failed to create socket: {$errstr}\n");
    @unlink($pidFile);
    exit(1);
}

chmod($socketPath, 0660);

fwrite(STDERR, "ACP daemon: listening on {$socketPath}\n");

// ---------------------------------------------------------------------------
// Main loop — accept connections, handle requests
// ---------------------------------------------------------------------------

$lastCleanup = microtime(true);

while ($running) {
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }

    if (!$running) {
        break;
    }

    // Periodic idle session cleanup (every 60 seconds)
    $now = microtime(true);
    if (($now - $lastCleanup) > 60.0) {
        $lastCleanup = $now;
        foreach ($sessions as $sid => $sess) {
            if (!$sess['busy'] && ($now - $sess['lastActivity']) > $idleTimeoutSeconds) {
                fwrite(STDERR, "ACP daemon: session {$sid} idle timeout, terminating agent.\n");
                terminateSession($sessions[$sid]);
                unset($sessions[$sid]);
            }
            // Also clean up dead agents
            if (!isAgentAlive($sess['process'])) {
                fwrite(STDERR, "ACP daemon: session {$sid} agent died, cleaning up.\n");
                terminateSession($sessions[$sid]);
                unset($sessions[$sid]);
            }
        }
    }

    // Accept connection with timeout (1 second)
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
        fwrite($client, json_encode(['ok' => true, 'protocol' => 2, 'sessions' => count($sessions)]) . "\n");
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

    // --- Session Start ---
    if ($action === 'session-start') {
        $sessionId = $request['sessionId'] ?? '';
        $command = $request['command'] ?? '';

        if ($sessionId === '' || $command === '') {
            fwrite($client, json_encode(['error' => 'sessionId and command are required.']) . "\n");
            fclose($client);
            continue;
        }

        // Reuse existing session
        if (isset($sessions[$sessionId]) && isAgentAlive($sessions[$sessionId]['process'])) {
            $sess = $sessions[$sessionId];
            $sessions[$sessionId]['lastActivity'] = microtime(true);
            fwrite($client, json_encode([
                'ok' => true,
                'agentName' => $sess['agentName'],
                'agentVersion' => $sess['agentVersion'],
                'reused' => true,
            ])
                . "\n");
            fclose($client);
            continue;
        }

        // Check session limit
        if (count($sessions) >= $maxSessions) {
            fwrite($client, json_encode([
                'error' => "Maximum number of concurrent sessions ({$maxSessions}) reached.",
            ])
                . "\n");
            fclose($client);
            continue;
        }

        $args = $request['args'] ?? [];
        $env = $request['env'] ?? [];

        try {
            fwrite(STDERR, "ACP daemon: starting session {$sessionId}...\n");
            $session = spawnAgent($command, $args, $env);
            $sessions[$sessionId] = $session;
            fwrite(
                STDERR,
                "ACP daemon: session {$sessionId} started — {$session['agentName']} {$session['agentVersion']}\n",
            );
            fwrite($client, json_encode([
                'ok' => true,
                'agentName' => $session['agentName'],
                'agentVersion' => $session['agentVersion'],
            ])
                . "\n");
        } catch (RuntimeException $e) {
            fwrite(STDERR, "ACP daemon: session {$sessionId} failed to start: {$e->getMessage()}\n");
            fwrite($client, json_encode(['error' => $e->getMessage()]) . "\n");
        }

        fclose($client);
        continue;
    }

    // --- Session Stop ---
    if ($action === 'session-stop') {
        $sessionId = $request['sessionId'] ?? '';

        if ($sessionId !== '' && isset($sessions[$sessionId])) {
            fwrite(STDERR, "ACP daemon: stopping session {$sessionId}.\n");
            terminateSession($sessions[$sessionId]);
            unset($sessions[$sessionId]);
        }

        fwrite($client, json_encode(['ok' => true]) . "\n");
        fclose($client);
        continue;
    }

    // --- Session Status ---
    if ($action === 'session-status') {
        $sessionId = $request['sessionId'] ?? '';

        if ($sessionId === '' || !isset($sessions[$sessionId])) {
            fwrite($client, json_encode(['ok' => true, 'active' => false]) . "\n");
            fclose($client);
            continue;
        }

        $sess = $sessions[$sessionId];
        $alive = isAgentAlive($sess['process']);

        if (!$alive) {
            terminateSession($sessions[$sessionId]);
            unset($sessions[$sessionId]);
        }

        fwrite($client, json_encode([
            'ok' => true,
            'active' => $alive,
            'agentName' => $sess['agentName'],
            'agentVersion' => $sess['agentVersion'],
            'busy' => $sess['busy'],
        ])
            . "\n");
        fclose($client);
        continue;
    }

    // --- Prompt ---
    if ($action === 'prompt') {
        $sessionId = $request['sessionId'] ?? '';

        if ($sessionId === '' || !isset($sessions[$sessionId])) {
            fwrite($client, json_encode(['error' => 'Session not found. Please reconnect.']) . "\n");
            fclose($client);
            continue;
        }

        if (!isAgentAlive($sessions[$sessionId]['process'])) {
            terminateSession($sessions[$sessionId]);
            unset($sessions[$sessionId]);
            fwrite($client, json_encode(['error' => 'Agent process died. Please reconnect.']) . "\n");
            fclose($client);
            continue;
        }

        if ($sessions[$sessionId]['busy']) {
            fwrite($client, json_encode(['error' => 'Session is busy processing another prompt.']) . "\n");
            fclose($client);
            continue;
        }

        $sessions[$sessionId]['busy'] = true;
        $sessions[$sessionId]['lastActivity'] = microtime(true);

        $response = handlePrompt($request, $sessions[$sessionId]);

        $sessions[$sessionId]['busy'] = false;
        $sessions[$sessionId]['lastActivity'] = microtime(true);

        fwrite($client, json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        fclose($client);
        continue;
    }

    fwrite($client, json_encode(['error' => "Unknown action: {$action}"]) . "\n");
    fclose($client);
}

// ---------------------------------------------------------------------------
// Cleanup — terminate all sessions
// ---------------------------------------------------------------------------

fwrite(STDERR, "ACP daemon: shutting down...\n");

foreach ($sessions as $sid => $sess) {
    fwrite(STDERR, "ACP daemon: terminating session {$sid}.\n");
    terminateSession($sessions[$sid]);
}
$sessions = [];

fclose($server);
@unlink($socketPath);
@unlink($pidFile);

fwrite(STDERR, "ACP daemon: stopped.\n");
exit(0);
