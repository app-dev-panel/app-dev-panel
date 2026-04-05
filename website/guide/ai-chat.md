---
title: AI Chat
description: "Connect an LLM provider (OpenRouter, Anthropic, OpenAI, or ACP local agents) for AI-powered debug analysis and chat in the debug panel and toolbar."
---

# AI Chat

ADP includes a built-in AI assistant that can analyze debug entries, answer questions about your application, and suggest fixes. It works in two places: the **Debug Panel** (full-featured chat and analysis) and the **Toolbar** (quick chat popup embedded in your page).

Both share the same backend connection — configure once in the panel, use everywhere.

## Connecting an LLM Provider

Navigate to **LLM** in the debug panel sidebar (`/debug/llm`). The connection card shows the current status and lets you connect to one of three supported providers.

### Supported Providers

| Provider | Auth Method | Default Model | Models |
|----------|-------------|---------------|--------|
| **OpenRouter** | OAuth (PKCE) | `anthropic/claude-sonnet-4` | Claude, GPT-4, Llama, Mistral, Gemini, and 200+ others |
| **Anthropic** | API Key | `claude-sonnet-4-20250514` | Claude model family |
| **OpenAI** | API Key | `gpt-4o` | GPT-4o, o1, ChatGPT models |
| **ACP** | None (local) | Agent-managed | Claude Code, Gemini CLI, Codex CLI, or any ACP-compatible agent |

### Connect via OpenRouter (OAuth)

1. Select **OpenRouter** in the provider toggle
2. Click **Connect with OpenRouter**
3. A new window opens with the OpenRouter authorization page
4. Approve the connection
5. The window closes automatically; the panel shows **Connected**

OpenRouter uses the OAuth PKCE flow — no API key needed. You get access to all models available on your OpenRouter account, including free-tier models.

### Connect via API Key (Anthropic / OpenAI)

1. Select **Anthropic** or **OpenAI** in the provider toggle
2. Enter your API key (`sk-ant-...` for Anthropic, `sk-proj-...` for OpenAI)
3. Click **Connect with API Key**
4. The panel shows **Connected**

API keys are stored server-side in `.llm-settings.json` inside the ADP storage directory. They are never sent to the frontend after the initial connection.

### Connect via ACP (Local Agent)

ACP (Agent Client Protocol) connects to a local AI agent running on your machine — no API keys or cloud services needed. The agent runs as a subprocess managed by ADP.

1. Select **ACP** in the provider toggle
2. Set the **Command** (default: `npx`) and **Arguments** (default: `@agentclientprotocol/claude-agent-acp`)
3. Click **Connect Agent**
4. ADP starts a background daemon and spawns an agent subprocess for your browser tab

**How it works:**

ADP runs a persistent daemon process that manages agent subprocesses via a Unix socket. Each browser tab gets its own isolated agent session — multiple users or tabs can connect simultaneously without interfering with each other.

```
Browser Tab 1 ──► ADP API ──► Daemon ──► Agent subprocess 1
Browser Tab 2 ──► ADP API ──► Daemon ──► Agent subprocess 2
```

**Available ACP adapters:**

| Adapter package | Agent |
|----------------|-------|
| `@agentclientprotocol/claude-agent-acp` | Claude Code (default) |
| `@anthropic-ai/gemini-agent-acp` | Gemini CLI |
| `@anthropic-ai/codex-agent-acp` | Codex CLI |

The command must be available on the system PATH of the server running ADP. The agent process inherits the server's environment, so API keys for the agent (e.g. `ANTHROPIC_API_KEY`) should be set in the server's environment.

**Session lifecycle:**

- Sessions are scoped to browser tabs (using `sessionStorage`)
- Idle sessions are automatically terminated after 30 minutes
- Maximum 10 concurrent sessions per daemon
- Disconnecting from the panel stops only your tab's agent session
- The daemon auto-restarts if it detects a protocol version mismatch (e.g. after an ADP update)

### Selecting a Model

After connecting, click the connection bar to expand settings. Use the **Model** dropdown to pick a model. For OpenRouter, toggle **Free** to filter free-tier models only.

### Custom Instructions

The **Custom instructions** field lets you set a system prompt that is prepended to every LLM request (both chat and analyze). Default:

> Reply in English. Be concise and actionable — focus on root causes and fixes, not descriptions of what the code does.

Change this to match your preferred language or analysis style.

### Request Timeout

The send button shows the current timeout (default 30s). Click the dropdown arrow to change it (10s–120s). Longer timeouts help with complex analysis on slower models.

## Panel: Chat Tab

The **Chat** tab at `/debug/llm` provides a full conversation interface:

- Type messages and get AI responses
- Multi-turn conversation with full context
- Markdown rendering in responses (code blocks, lists, tables)
- Chat history stored server-side and browsable in the **History** accordion
- Retry failed messages with one click
- Delete individual history entries or clear all

The chat does not automatically include debug entry context — it is a general-purpose conversation with your configured LLM.

## Panel: Analyze Tab

The **Analyze Debug Entry** tab lets you send debug data to the AI for analysis:

1. Select one or more debug entries from the list (use checkboxes)
2. Filter entries by URL/command text or quick filters (4xx, 5xx, errors, slow requests)
3. Optionally type analysis instructions (e.g., "Why is this query slow?")
4. Click **Analyze**

The selected entries' data (request, response, timing, database queries, exceptions, logs) is sent as context to the LLM. The response appears inline with markdown formatting.

Context is truncated to 12,000 characters to stay within model limits.

## Toolbar: AI Chat Popup

The toolbar embeds a lightweight AI chat popup accessible from any page in your application.

### Opening the Chat

Click the **duck icon** button in the toolbar to open the Debug Duck chat popup.

### How It Works

The toolbar chat uses the **same backend connection** configured in the panel. No separate setup needed.

```
Toolbar (your page)          Panel (configured once)
     │                              │
     │  GET /debug/api/llm/status   │
     ├─────────────────────────────►│
     │  ◄── connected: true ────────┤
     │                              │
     │  POST /debug/api/llm/chat    │
     ├─────────────────────────────►│──► LLM Provider API
     │  ◄── AI response ───────────┤◄──
```

### Connection Status

The status dot in the chat header reflects the real connection state:

| Dot Color | Meaning |
|-----------|---------|
| Green | Connected — AI chat active |
| Red | Not connected — configure in panel first |
| Gray | Loading status... |

When not connected, a warning banner appears with a link to open the panel's LLM settings.

### Context-Aware Conversations

The toolbar automatically includes the current debug entry's context in the first message:

- Request method, path, and status code
- Response time and memory usage
- Database query count and slowest queries
- Exceptions (class and message)
- Log and deprecation counts

This means you can ask questions like "Why is this slow?" or "Explain this error" and the AI already has the relevant debug data.

### Features

- **Multi-turn conversation** — follow-up questions maintain context
- **Suggestion chips** — quick actions like "Analyze request", "Performance tips", "Explain errors", "Suggest fixes"
- **Chat history** — conversations are saved to the shared history (visible in the panel too)
- **Draggable and resizable** — move the popup anywhere, resize from the bottom-right corner
- **Auto-reset on entry change** — selecting a new debug entry resets the conversation with a fresh summary

## Settings Storage

All LLM settings are stored in a single JSON file:

```
{storage_path}/.llm-settings.json
```

Contents:

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `apiKey` | string \| null | `null` | Provider API key or OAuth token |
| `provider` | string | `"openrouter"` | Active provider: `openrouter`, `anthropic`, `openai`, or `acp` |
| `model` | string \| null | `null` | Selected model ID (uses provider default if null) |
| `timeout` | number | `30` | Request timeout in seconds (5–300) |
| `customPrompt` | string | *(see above)* | System prompt prepended to all requests |
| `acpCommand` | string | `""` | ACP agent command (e.g. `npx`) |
| `acpArgs` | string[] | `[]` | Arguments for the ACP command |
| `acpEnv` | object | `{}` | Environment variables for the agent process |

Chat history is stored separately in `.llm-history.json` (max 100 entries, FIFO).

## Architecture

```
┌─────────────┐    ┌─────────────┐
│ Debug Panel │    │   Toolbar   │
│  (Chat tab) │    │ (AI popup)  │
└──────┬──────┘    └──────┬──────┘
       │                  │
       │  RTK Query API   │  RTK Query API
       │  + X-Acp-Session │  + X-Acp-Session
       │                  │
       ▼                  ▼
┌─────────────────────────────────┐
│     /debug/api/llm/*            │
│     LlmController (PHP)        │
├─────────────────────────────────┤
│     LlmProviderService          │
│     ├─ OpenRouter (OAuth/key)   │
│     ├─ Anthropic (API key)      │
│     ├─ OpenAI (API key)         │
│     └─ ACP (local agent) ───────┼──► AcpDaemonManager
├─────────────────────────────────┤     ├─ Unix socket IPC
│     FileLlmSettings             │     └─ acp-daemon-runner.php
│     FileLlmHistoryStorage       │         ├─ Session 1 → Agent 1
│     (.llm-settings.json)        │         ├─ Session 2 → Agent 2
│     (.llm-history.json)         │         └─ Session N → Agent N
└─────────────────────────────────┘
```

The frontend LLM API (`llmApi`) lives in the shared SDK package (`@app-dev-panel/sdk/API/Llm/Llm`), so both the panel and toolbar import from the same source. Both Redux stores include the LLM reducers and middlewares, sharing the same RTK Query cache behavior.

For ACP, each browser tab generates a UUID stored in `sessionStorage` and sends it as `X-Acp-Session` header on every LLM API request. The daemon maps session IDs to agent subprocesses, ensuring tab-level isolation.

## REST API

See [LLM API endpoints](/api/rest#llm-api) for the full list of 15 endpoints.
