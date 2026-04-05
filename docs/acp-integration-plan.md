# ACP Integration Plan — ACP as LLM Provider

Status: 2026-04-04. Active implementation.

## Motivation

ADP's LLM module currently supports 3 providers: OpenRouter, Anthropic (direct), OpenAI.
All use HTTP API calls to send chat messages and receive responses.

ACP (Agent Client Protocol) adds a **4th provider type** that connects to a local ACP agent
(Claude Code, Gemini CLI, Codex CLI, etc.) instead of calling an HTTP API.

**Current architecture:**
```
ADP Frontend (Chat/Analyze) → HTTP → ADP Backend → HTTP API → LLM Provider (OpenRouter/Anthropic/OpenAI)
```

**New ACP provider:**
```
ADP Frontend (Chat/Analyze) → HTTP → ADP Backend → stdio/ACP → Claude Code → LLM (Claude)
```

**Key insight:** ADP becomes an **ACP client** (role normally played by editors like Zed/JetBrains).
Claude Code (or any ACP agent) is the **ACP server**, spawned as a subprocess.

## Value Proposition

- **No API keys in ADP** — Claude Code handles its own auth (user already has it configured)
- **Smarter responses** — Claude Code has tools (file reading, terminal, web search), not just raw LLM
- **Provider-agnostic** — swap Claude Code for Gemini CLI, Codex, Goose, or any ACP agent
- **Local-first** — code never leaves the machine; ACP is a local protocol
- **Existing debug tools** — ACP agent can use ADP's MCP server for deep debug data access

## Architecture

### New Components

```
libs/API/src/Llm/Acp/
├── AcpTransport.php          # proc_open subprocess, read/write JSON-RPC over pipes
├── AcpClient.php             # ACP protocol: initialize → session/new → session/prompt
└── AcpResponse.php           # Value object: collected response text + metadata
```

### Integration Points

```
LlmController::chat()
    → LlmProviderService::sendChat('acp', $messages, ...)
        → AcpClient::prompt($messages)
            → AcpTransport::spawn('claude', [...])
            → send initialize → receive capabilities
            → send session/new → receive session_id
            → send session/prompt → collect session/update stream
            → return AcpResponse(text, stopReason)
        → normalize to OpenAI-compatible format
    → return JSON response to frontend
```

### Process Lifecycle (MVP)

Per-request subprocess: spawn Claude Code, do full ACP lifecycle, close.

```
HTTP Request (chat)
  1. proc_open('claude', pipes=[stdin, stdout, stderr])
  2. Send: initialize {protocolVersion: 1, capabilities: {}}
  3. Recv: initialize response {capabilities, agentInfo}
  4. Send: session/new {workingDirectory: $cwd}
  5. Recv: session/new response {sessionId: "..."}
  6. Send: session/prompt {sessionId, prompt: {content: [{type: text, text: "..."}]}}
  7. Recv: session/update notifications (streaming text chunks)
  8. Recv: session/prompt response {stopReason: "end_turn"}
  9. Collect all text chunks → aggregate response
  10. Close process
```

**Trade-off:** ~2-5s startup overhead per request. Acceptable for a debugging tool
where LLM response itself takes 5-30s. Persistent process optimization planned for Phase 2.

### Settings

ACP provider uses these settings (stored in `.llm-settings.json`):

```json
{
  "provider": "acp",
  "acpCommand": "claude",
  "acpArgs": [],
  "acpEnv": {},
  "timeout": 60,
  "customPrompt": "..."
}
```

No `apiKey` needed — the ACP agent handles its own authentication.

`isConnected()` for ACP returns `true` when the configured command exists on the system.

## ACP Protocol Messages

### initialize (request)

```json
→ {"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":1,"capabilities":{},"clientInfo":{"name":"ADP","version":"1.0.0"}}}
← {"jsonrpc":"2.0","id":1,"result":{"protocolVersion":1,"capabilities":{},"agentInfo":{"name":"Claude Code","version":"..."}}}
```

### session/new (request)

```json
→ {"jsonrpc":"2.0","id":2,"method":"session/new","params":{}}
← {"jsonrpc":"2.0","id":2,"result":{"sessionId":"uuid-here"}}
```

### session/prompt (request + streaming notifications)

```json
→ {"jsonrpc":"2.0","id":3,"method":"session/prompt","params":{"sessionId":"uuid","prompt":{"content":[{"type":"text","text":"Why is my last request slow?"}]}}}

← {"jsonrpc":"2.0","method":"session/update","params":{"sessionId":"uuid","update":{"type":"message_chunk","role":"assistant","content":[{"type":"text","text":"Let me"}]}}}
← {"jsonrpc":"2.0","method":"session/update","params":{"sessionId":"uuid","update":{"type":"message_chunk","role":"assistant","content":[{"type":"text","text":" analyze"}]}}}
...
← {"jsonrpc":"2.0","id":3,"result":{"stopReason":"end_turn"}}
```

## Implementation Phases

### Phase 1: Core ACP Provider (current)

- [x] `AcpTransport` — subprocess management via `proc_open`
- [x] `AcpClient` — ACP protocol (initialize, session/new, session/prompt)
- [x] `AcpResponse` — response value object
- [x] `LlmProviderService` — add `acp` provider with `sendAcpChat()`
- [x] `LlmSettingsInterface` / `FileLlmSettings` — ACP command settings
- [x] `LlmController` — ACP connect (verify command exists)
- [x] Unit tests

### Phase 2: Persistent Process (future)

- Long-lived ACP agent daemon (survives between HTTP requests)
- Session reuse for multi-turn conversations
- `acp:daemon` CLI command
- Process health monitoring + auto-restart

### Phase 3: MCP Forwarding (future)

- Pass ADP's MCP server config to ACP agent during session/new
- Agent auto-discovers debug tools
- Deep integration: agent queries debug data autonomously

### Phase 4: Streaming (future)

- SSE endpoint for real-time response streaming
- Frontend renders tokens as they arrive
- Plan updates displayed in UI

## Configuration Examples

### Connect via ADP UI

1. Open ADP → AI Chat → Connection Settings
2. Select provider: "ACP Agent"
3. Command: `claude` (auto-detected if on PATH)
4. Click Connect

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `ADP_ACP_COMMAND` | `claude` | ACP agent binary |
| `ADP_ACP_ARGS` | (none) | Additional CLI arguments |

## Comparison with Other Providers

| Aspect | OpenRouter/Anthropic/OpenAI | ACP (Claude Code) |
|--------|---------------------------|-------------------|
| Auth | API key in ADP settings | Agent's own auth (pre-configured) |
| Transport | HTTPS to remote API | stdio to local subprocess |
| Capabilities | Raw LLM only | LLM + tools (file, terminal, web) |
| Latency | ~1s (network) | ~3-5s (process spawn) |
| Privacy | Data sent to API | Everything local |
| Cost | Per-token billing | Included in Claude subscription |
| Agent swap | Change API key | Change command (claude → gemini) |
