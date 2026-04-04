---
description: "Complete ADP REST API reference: debug entries, summaries, collector data, inspector queries, and ingestion."
---

# REST Endpoints

## Debug API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/` | List all debug entries (summaries) |
| GET | `/debug/api/summary/{id}` | Single entry summary |
| GET | `/debug/api/view/{id}` | Full entry data (optionally filtered by collector) |
| GET | `/debug/api/dump/{id}` | Dump objects for entry |
| GET | `/debug/api/object/{id}/{objectId}` | Specific object from dump |
| GET | `/debug/api/settings` | Debug settings (path mapping) |

### Example: List entries

```
GET /debug/api/
```

```json
{
    "id": null,
    "data": [
        {
            "id": "abc123",
            "collectors": ["request", "log", "event"],
            "url": "/api/users",
            "method": "GET",
            "status": 200,
            "time": 1234567890
        }
    ],
    "error": null,
    "success": true
}
```

### Example: View entry

```
GET /debug/api/view/abc123?collector=log
```

Returns full collected data for the specified entry, optionally filtered to a single collector.

## OTLP Trace Ingestion

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/otlp/v1/traces` | Ingest OpenTelemetry traces in OTLP format |

## LLM API

All LLM endpoints share the same settings stored in `.llm-settings.json`. Both the debug panel and the toolbar use these endpoints — configure once, use everywhere.

See the [AI Chat guide](/guide/ai-chat) for user-facing documentation.

### LLM Providers

| Provider | Auth | How it works |
|----------|------|--------------|
| `openrouter` | API key or OAuth | HTTP calls to OpenRouter API (default) |
| `anthropic` | API key or OAuth token | HTTP calls to Anthropic Messages API |
| `openai` | API key | HTTP calls to OpenAI Chat Completions API |
| `acp` | None (local agent) | Spawns agent (e.g. Claude Code) as subprocess via Agent Client Protocol |

### Connection

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/llm/status` | Connection status, provider, model, timeout, custom prompt |
| POST | `/debug/api/llm/connect` | Connect with API key or ACP agent |
| POST | `/debug/api/llm/disconnect` | Clear stored credentials |

#### Status

```
GET /debug/api/llm/status
```

```json
{
    "data": {
        "connected": true,
        "provider": "openrouter",
        "model": "anthropic/claude-sonnet-4",
        "timeout": 30,
        "customPrompt": "Reply in English. Be concise and actionable..."
    }
}
```

#### Connect with API Key

```
POST /debug/api/llm/connect
Content-Type: application/json

{"provider": "anthropic", "apiKey": "sk-ant-..."}
```

Supported providers: `openrouter`, `anthropic`, `openai`.

#### Connect with ACP

The ACP provider uses the [Agent Client Protocol](https://agentclientprotocol.com/) to communicate with local AI agents over stdio. ADP acts as an ACP client, spawning the agent as a subprocess per request.

```
POST /debug/api/llm/connect
Content-Type: application/json

{
  "provider": "acp",
  "acpCommand": "claude",
  "acpArgs": ["--model", "opus"],
  "acpEnv": {"ANTHROPIC_MODEL": "claude-sonnet-4-20250514"}
}
```

- `acpCommand` — Agent CLI binary (default: `claude`). Must be on system PATH.
- `acpArgs` — Additional CLI arguments passed to the agent.
- `acpEnv` — Environment variables merged into the agent's environment.

**Protocol lifecycle per chat request:** `initialize` → `session/new` → `session/prompt` (with streaming `session/update` notifications) → close.

**Supported agents:** Claude Code, Gemini CLI, Codex CLI, or any agent implementing ACP v1.

### OAuth (OpenRouter)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/llm/oauth/initiate` | Start OAuth PKCE flow, returns `authUrl` and `codeVerifier` |
| POST | `/debug/api/llm/oauth/exchange` | Exchange authorization `code` + `codeVerifier` for API key |

#### Initiate

```
POST /debug/api/llm/oauth/initiate
Content-Type: application/json

{"callbackUrl": "https://localhost:5173/debug/llm/callback"}
```

```json
{
    "data": {
        "authUrl": "https://openrouter.ai/auth?...",
        "codeVerifier": "..."
    }
}
```

Open `authUrl` in a popup. After user approval, OpenRouter redirects to `callbackUrl?code=...`. Exchange the code:

```
POST /debug/api/llm/oauth/exchange
Content-Type: application/json

{"code": "...", "codeVerifier": "..."}
```

### Settings

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/llm/models` | List available models for the connected provider |
| POST | `/debug/api/llm/model` | Set active model: `{"model": "anthropic/claude-sonnet-4"}` |
| POST | `/debug/api/llm/timeout` | Set timeout in seconds (5–300): `{"timeout": 60}` |
| POST | `/debug/api/llm/custom-prompt` | Set system prompt: `{"customPrompt": "..."}` |

All settings endpoints return the updated `LlmStatus` object (same shape as `GET /status`).

### Chat & Analysis

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/llm/chat` | Send chat completion request |
| POST | `/debug/api/llm/analyze` | Analyze debug entry data with AI |

#### Chat

```
POST /debug/api/llm/chat
Content-Type: application/json

{
    "messages": [
        {"role": "user", "content": "Why is my /api/users endpoint slow?"}
    ],
    "model": "anthropic/claude-sonnet-4",
    "temperature": 0.7
}
```

```json
{
    "data": {
        "choices": [{"message": {"role": "assistant", "content": "Based on..."}}],
        "model": "anthropic/claude-sonnet-4",
        "usage": {"prompt_tokens": 50, "completion_tokens": 200}
    }
}
```

The custom prompt (if set) is automatically prepended as a system message. All responses are normalized to the OpenAI-compatible format regardless of provider.

#### Analyze

```
POST /debug/api/llm/analyze
Content-Type: application/json

{
    "context": {"request": {"method": "GET", "path": "/api/users"}, "db": {"queries": {"total": 47}}},
    "prompt": "Why are there so many queries?"
}
```

Context is truncated to 12,000 characters. Returns `{"data": {"analysis": "...", "model": "..."}}`.

### History

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/llm/history` | Get all history entries (max 100, FIFO) |
| POST | `/debug/api/llm/history` | Add entry: `{query, response, timestamp, error?}` |
| DELETE | `/debug/api/llm/history/{index}` | Delete entry by index |
| DELETE | `/debug/api/llm/history` | Clear all history |

History is shared between the panel and toolbar — entries added from either appear in both.

## Ingestion API

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/ingest/` | Ingest single debug entry |
| POST | `/debug/api/ingest/batch` | Ingest multiple entries |
| POST | `/debug/api/ingest/log` | Shorthand: ingest a single log entry |
| GET | `/debug/api/ingest/openapi.json` | OpenAPI 3.1 specification |

## Service Registry API

| Method | Path | Description |
|--------|------|-------------|
| POST | `/debug/api/services/register` | Register an external service |
| POST | `/debug/api/services/heartbeat` | Keep service online |
| GET | `/debug/api/services/` | List registered services |
| DELETE | `/debug/api/services/{service}` | Deregister a service |
