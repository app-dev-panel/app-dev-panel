# ACP Integration Plan — AI Debugging Agent for Editors

Status: 2026-04-04. Draft.

## Motivation

ADP already exposes debug data to AI via **MCP tools** (passive: agent calls tools). ACP integration adds a new dimension — ADP becomes an **active AI debugging agent** that editors launch directly. Users ask debugging questions in natural language inside their IDE, and ADP's agent answers using real debug data.

**Current flow (MCP):**
```
Editor → AI Agent (Claude Code, etc.) → MCP → ADP Tools → Debug Data
         ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         Agent is external, ADP is just a tool server
```

**Proposed flow (ACP):**
```
Editor → ADP ACP Agent → LLM API + ADP Tools → Debug Data
         ^^^^^^^^^^^^^
         ADP IS the agent, runs in editor natively
```

**Value proposition:**
- Zero-config debugging AI in any ACP-compatible editor (Zed, JetBrains, Neovim, Emacs)
- Deep domain knowledge — agent is purpose-built for debugging, not a generic assistant
- Direct access to debug storage — no MCP proxy overhead
- Structured plans and progress for multi-step debugging workflows
- Editor gets file links, terminal commands, and permission prompts natively via ACP

## Architecture

### New Library: `libs/AcpAgent/`

```
libs/AcpAgent/
├── bin/adp-acp                              # Standalone binary entry point
├── composer.json                            # Deps: kernel, mcpserver, LLM client
├── CLAUDE.md                                # Module documentation
├── src/
│   ├── AcpAgent.php                         # ACP JSON-RPC protocol handler
│   ├── AcpTransport.php                     # stdio transport (newline-delimited JSON-RPC)
│   ├── Session/
│   │   ├── Session.php                      # Conversation session state
│   │   ├── SessionStore.php                 # Session persistence (file-based)
│   │   └── SessionMessage.php               # Message in conversation history
│   ├── Llm/
│   │   ├── LlmClientInterface.php           # Abstraction over LLM providers
│   │   ├── AnthropicClient.php              # Claude API client
│   │   ├── OpenAiClient.php                 # OpenAI API client
│   │   └── LlmResponse.php                  # Streamed response chunks
│   ├── Tool/
│   │   ├── AgentToolExecutor.php            # Bridges MCP ToolInterface → LLM tool_use
│   │   └── EditorToolProxy.php             # Proxies ACP client methods (fs, terminal)
│   ├── Prompt/
│   │   ├── SystemPromptBuilder.php          # Builds system prompt with app context
│   │   └── ContextEnricher.php              # Auto-attaches recent debug entries to context
│   └── Config/
│       └── AcpConfig.php                    # Agent configuration (LLM provider, model, etc.)
└── tests/
    └── Unit/
        ├── AcpAgentTest.php
        ├── Session/SessionTest.php
        ├── Llm/AnthropicClientTest.php
        └── Tool/AgentToolExecutorTest.php
```

### Dependency Graph

```
AcpAgent → McpServer (reuses ToolInterface, existing debug tools)
AcpAgent → Kernel (StorageInterface for direct data access)
AcpAgent → [External] LLM API (Claude, OpenAI, etc.)

AcpAgent does NOT depend on: API, Adapters, Frontend
```

### Component Responsibilities

| Component | Purpose |
|-----------|---------|
| `AcpAgent` | ACP protocol handler: initialize, authenticate, session/*, capability negotiation |
| `AcpTransport` | stdio JSON-RPC (same pattern as `StdioTransport` in McpServer) |
| `Session` | Tracks conversation history, session ID, working directory |
| `SessionStore` | Persists sessions to disk for `session/load` support |
| `LlmClientInterface` | Provider-agnostic LLM API: `chat(messages, tools) → stream<chunks>` |
| `AgentToolExecutor` | Wraps existing MCP `ToolInterface` implementations as LLM function calls |
| `EditorToolProxy` | Calls editor via ACP client methods: `fs/read_text_file`, `terminal/create` |
| `SystemPromptBuilder` | Generates system prompt with app stack info, available collectors, storage stats |
| `ContextEnricher` | Auto-attaches latest debug entry summary to each prompt for implicit context |
| `AcpConfig` | Reads config from env vars / config file: LLM API key, model, storage path |

## Protocol Implementation

### ACP Methods — Agent Side (editor calls agent)

| Method | Implementation |
|--------|---------------|
| `initialize` | Return capabilities: `loadSession`, `promptCapabilities.text`, MCP support |
| `authenticate` | Validate LLM API key if not in env; return auth status |
| `session/new` | Create Session, attach MCP servers from params, set working dir |
| `session/load` | Load Session from SessionStore by ID |
| `session/prompt` | Main loop: enrich context → call LLM → stream updates → execute tools → respond |
| `session/cancel` | Abort current LLM stream, return partial response |

### ACP Methods — Client Side (agent calls editor)

| Method | When Used |
|--------|-----------|
| `session/update` | Stream LLM response tokens, tool call progress, plan updates |
| `session/request_permission` | Before executing terminal commands or writing files |
| `fs/read_text_file` | When agent needs to read source code referenced in stack traces |
| `fs/write_text_file` | When agent suggests a fix and user approves |
| `terminal/create` | Run diagnostic commands (e.g., `php artisan route:list`) |

### Prompt Turn Flow

```
1. Editor sends session/prompt with user message
   │
2. ContextEnricher attaches latest debug entry summary
   │
3. SystemPromptBuilder generates system prompt:
   │  - "You are ADP debugging agent for {framework} app"
   │  - Available tools: list_debug_entries, view_entry, search_logs, ...
   │  - Current storage: {N} entries, latest at {timestamp}
   │
4. LlmClientInterface.chat(messages, tools) → stream
   │
5. For each stream chunk:
   │  ├─ Text → send session/update (message_chunk)
   │  ├─ Tool call → execute via AgentToolExecutor or EditorToolProxy
   │  │   ├─ Debug tools: AgentToolExecutor.execute() → direct storage access
   │  │   ├─ File read: EditorToolProxy → fs/read_text_file → editor
   │  │   └─ Terminal: request_permission → terminal/create → editor
   │  └─ Plan → send session/update (plan_update)
   │
6. On stream end → send session/prompt response with stop_reason
```

## Implementation Phases

### Phase 1: Core Protocol (MVP)

**Goal:** Working ACP agent that editors can launch and chat with about debug data.

**Scope:**
- `AcpAgent` — JSON-RPC handler for `initialize`, `session/new`, `session/prompt`, `session/cancel`
- `AcpTransport` — stdio transport (copy pattern from McpServer's StdioTransport)
- `AnthropicClient` — Claude API integration with streaming
- `AgentToolExecutor` — bridge all 6 existing MCP debug tools
- `SystemPromptBuilder` — basic system prompt with storage context
- `AcpConfig` — env-based config: `ADP_LLM_PROVIDER`, `ADP_LLM_API_KEY`, `ADP_LLM_MODEL`, `ADP_STORAGE_PATH`
- `bin/adp-acp` — standalone binary
- Unit tests for all components

**Entry point config (for editors):**
```json
{
  "agents": {
    "adp": {
      "command": "php",
      "args": ["vendor/bin/adp-acp"],
      "env": {
        "ADP_STORAGE_PATH": "/path/to/debug-data",
        "ADP_LLM_API_KEY": "sk-ant-..."
      }
    }
  }
}
```

**Deliverables:**
- [ ] `AcpTransport` — stdio JSON-RPC
- [ ] `AcpAgent` — protocol handler with initialize + session/new + session/prompt
- [ ] `LlmClientInterface` + `AnthropicClient` (Claude API with streaming)
- [ ] `AgentToolExecutor` — wraps existing ToolInterface as LLM function calls
- [ ] `SystemPromptBuilder` — debug-focused system prompt
- [ ] `AcpConfig` — env-based configuration
- [ ] `bin/adp-acp` — binary entry point
- [ ] Unit tests (target: 90%+ coverage for new code)

**Estimated complexity:** ~800–1000 lines of PHP + tests.

### Phase 2: Editor Integration

**Goal:** File operations and terminal commands through the editor.

**Scope:**
- `EditorToolProxy` — calls editor's ACP client methods
- `fs/read_text_file` — agent reads source files through editor
- `fs/write_text_file` — agent suggests fixes, writes through editor
- `session/request_permission` — permission flow for destructive actions
- `terminal/create` — run framework CLI commands through editor terminal
- `ContextEnricher` — auto-attaches recent debug data to prompts

**New agent capabilities:**
- Read stack trace → auto-fetch source files from editor → include in LLM context
- Suggest code fixes → write through editor (with permission)
- Run `php artisan` / `./yii` / `bin/console` diagnostics through editor terminal

**Deliverables:**
- [ ] `EditorToolProxy` with fs + terminal support
- [ ] Permission request flow
- [ ] `ContextEnricher` — auto-attach debug context
- [ ] Stack trace → source file resolution
- [ ] Tests for editor proxy methods

**Estimated complexity:** ~400–500 lines.

### Phase 3: Sessions and Plans

**Goal:** Persistent sessions and structured debugging plans.

**Scope:**
- `Session` / `SessionStore` — file-based session persistence
- `session/load` — resume previous debugging sessions
- Plan generation — agent creates structured debugging plans via `session/update`
- Multi-turn debugging — agent remembers previous findings within a session

**Example plan output:**
```json
{
  "type": "plan_update",
  "plan": {
    "entries": [
      {"content": "Identify slow database queries", "status": "completed", "priority": "high"},
      {"content": "Check N+1 query patterns", "status": "in_progress", "priority": "high"},
      {"content": "Review middleware execution timeline", "status": "pending", "priority": "medium"},
      {"content": "Suggest optimization strategy", "status": "pending", "priority": "medium"}
    ]
  }
}
```

**Deliverables:**
- [ ] `Session` — conversation state management
- [ ] `SessionStore` — file persistence under storage path
- [ ] `session/load` handler in AcpAgent
- [ ] Plan streaming via session/update
- [ ] Tests

**Estimated complexity:** ~300–400 lines.

### Phase 4: Multi-Provider LLM Support

**Goal:** Support multiple LLM providers beyond Claude.

**Scope:**
- `OpenAiClient` — OpenAI API (GPT-4o, o3)
- `OllamaClient` — local models via Ollama (privacy-sensitive environments)
- Provider auto-detection from API key format
- Model selection via `ADP_LLM_MODEL` env var

**Deliverables:**
- [ ] `OpenAiClient` — OpenAI-compatible API
- [ ] `OllamaClient` — local model support
- [ ] Provider auto-detection
- [ ] Tests per provider

**Estimated complexity:** ~300–400 lines.

### Phase 5: CLI Command and Adapter Integration

**Goal:** Seamless setup for framework users.

**Scope:**
- `acp:serve` CLI command (parallel to `mcp:serve`)
- Auto-configuration in adapters: Symfony bundle, Laravel service provider, Yii3 config
- Composer post-install script that prints ACP config for the detected editor
- Frontend MCP page extended with ACP configuration section

**CLI command:**
```bash
php vendor/bin/adp acp:serve --storage-path=/path --llm-provider=anthropic
```

**Adapter auto-config example (Laravel):**
```php
// config/adp.php
return [
    'acp' => [
        'enabled' => env('ADP_ACP_ENABLED', true),
        'llm_provider' => env('ADP_LLM_PROVIDER', 'anthropic'),
        'llm_model' => env('ADP_LLM_MODEL', 'claude-sonnet-4-6'),
    ],
];
```

**Deliverables:**
- [ ] `AcpServeCommand` — CLI entry point
- [ ] Adapter integration for all 4 frameworks
- [ ] Frontend config section
- [ ] Documentation

**Estimated complexity:** ~400–500 lines.

### Phase 6: Advanced Features

**Goal:** Differentiation from generic AI agents.

**Scope:**
- **Anomaly detection prompts** — agent proactively flags: "This request has 47 DB queries, 3x more than average"
- **Comparative analysis** — "Compare request X vs Y: what changed?"
- **Performance profiling mode** — agent generates flame chart descriptions from timeline data
- **Multi-app correlation** — trace requests across microservices using ADP's multi-app support
- **Custom tool registration** — users register app-specific diagnostic tools

## System Prompt Design

```
You are ADP (Application Development Panel) debugging agent.
You help developers debug {framework} applications by analyzing runtime data.

Available debug tools:
- list_debug_entries: Query recent HTTP requests and console commands
- view_debug_entry: View full debug data for a specific entry
- search_logs: Search log messages across entries
- analyze_exception: Analyze exceptions with stack traces and context
- view_database_queries: Inspect SQL queries, find N+1 problems
- view_timeline: View event timeline for a request

Editor tools (require permission):
- Read source files from the project
- Write suggested fixes to files
- Run terminal commands (framework CLI, composer, etc.)

Storage info:
- Path: {storage_path}
- Entries: {entry_count}
- Latest entry: {latest_timestamp} — {latest_url} ({latest_status})
- Framework: {detected_framework}

Guidelines:
- Always start by listing recent debug entries to orient yourself
- When analyzing exceptions, fetch the source file around the error line
- For performance issues, check both database queries and timeline
- Suggest concrete fixes with file paths and line numbers
- Use structured plans for multi-step debugging tasks
- Be concise — developers want answers, not explanations
```

## Configuration

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `ADP_STORAGE_PATH` | Yes | — | Path to ADP debug storage |
| `ADP_LLM_PROVIDER` | No | `anthropic` | LLM provider: `anthropic`, `openai`, `ollama` |
| `ADP_LLM_API_KEY` | Yes* | — | API key (*not needed for Ollama) |
| `ADP_LLM_MODEL` | No | `claude-sonnet-4-6` | Model identifier |
| `ADP_LLM_BASE_URL` | No | provider default | Custom API endpoint |
| `ADP_ACP_SESSION_DIR` | No | `{storage}/.acp-sessions` | Session persistence directory |

### Editor Configuration Examples

**Zed (`settings.json`):**
```json
{
  "agent_client_protocol": {
    "agents": {
      "adp": {
        "command": "php",
        "args": ["vendor/bin/adp-acp"],
        "env": {
          "ADP_STORAGE_PATH": "./runtime/debug",
          "ADP_LLM_API_KEY": "sk-ant-..."
        }
      }
    }
  }
}
```

**JetBrains (ACP Agent Registry or manual):**
```json
{
  "command": "php",
  "args": ["vendor/bin/adp-acp"],
  "env": {
    "ADP_STORAGE_PATH": "./var/debug",
    "ADP_LLM_API_KEY": "sk-ant-..."
  }
}
```

**Neovim (CodeCompanion):**
```lua
require("codecompanion").setup({
  acp_agents = {
    adp = {
      command = "php",
      args = { "vendor/bin/adp-acp" },
      env = {
        ADP_STORAGE_PATH = "./runtime/debug",
        ADP_LLM_API_KEY = os.getenv("ANTHROPIC_API_KEY"),
      },
    },
  },
})
```

## Key Design Decisions

### 1. Reuse MCP Tools, Don't Duplicate

AcpAgent wraps the same `ToolInterface` implementations from `libs/McpServer/src/Tool/Debug/`. No code duplication. `AgentToolExecutor` converts MCP tool schemas to LLM function call format and routes execution.

### 2. LLM Is External, Not Embedded

ADP doesn't bundle an LLM. It calls external APIs (Claude, OpenAI, Ollama). This keeps the library lightweight and lets users choose their provider. Privacy-conscious users can use Ollama for fully local operation.

### 3. Editor Is the UI

The agent doesn't render its own UI. It uses ACP's streaming (`session/update`) for text, plans, and tool progress. The editor renders everything. This means zero frontend work for ACP support.

### 4. Sessions Are Optional

MVP works without session persistence. `session/load` is an optional capability. Persistence is added in Phase 3.

### 5. Adapter-Independent Core

`libs/AcpAgent/` depends only on Kernel and McpServer. No adapter dependency. The agent receives `StorageInterface` and works with any framework's debug data.

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| ACP spec still evolving (v0.x) | High | Medium | Pin to specific protocol version, abstract transport layer |
| LLM API costs for users | Medium | Medium | Default to cheaper model (Sonnet), support Ollama for free local option |
| PHP process for long-running agent | Medium | Low | Agent is spawned per-session by editor, lightweight event loop |
| Tool call latency (storage I/O + LLM) | Low | Medium | Stream responses, parallel tool calls where possible |
| Editor ACP support gaps | Medium | Low | Stdio transport is universal; graceful degradation for optional capabilities |

## Success Metrics

- Working `bin/adp-acp` that Zed/JetBrains can launch as ACP agent
- User asks "why was my last request slow?" → agent fetches timeline + DB queries → answers with specific bottleneck
- User asks "find N+1 queries" → agent scans entries → reports with file:line references
- User asks "what exceptions happened today?" → agent searches + analyzes → suggests fixes with code
- End-to-end latency: first token < 2s, full response < 10s for typical queries
