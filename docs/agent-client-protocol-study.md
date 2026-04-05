# Agent Client Protocol (ACP) — Research Study

> Research date: 2026-04-04

## Disambiguation

There are two different protocols abbreviated "ACP":

1. **Agent Client Protocol** (Zed Industries) — standardizes editor ↔ agent communication. **This document covers this protocol.**
2. **Agent Communication Protocol** (IBM Research) — agent-to-agent communication; merged with Google's A2A under the Linux Foundation. Separate initiative, different goals.

---

## 1. What Is ACP

The Agent Client Protocol is an **open standard that standardizes communication between code editors/IDEs and AI coding agents**. Created by **Zed Industries** (Nathan Sobo, co-founder), launched August 2025, Apache 2.0 licensed.

ACP does for AI coding agents what **LSP (Language Server Protocol)** did for language servers: any agent works in any editor through a single integration.

**Problems solved:**
- **Integration overhead**: N agents × M editors = N×M integrations → reduced to N+M
- **Vendor lock-in**: developers freely choose any agent with any editor
- **Fragmentation**: agents and editors evolve independently while staying compatible

**Privacy-first**: code never leaves the user's machine unless explicitly authorized.

---

## 2. Official Resources

| Resource | URL |
|----------|-----|
| Official website | agentclientprotocol.com |
| GitHub repo | github.com/agentclientprotocol/agent-client-protocol |
| Zed ACP page | zed.dev/acp |
| JetBrains docs | jetbrains.com/help/ai-assistant/acp.html |
| Protocol version | v0.11.4 (35 releases as of April 2026) |
| License | Apache 2.0 (no CLA) |
| Repo stats | ~2,700 stars, 214 forks, 1,084 commits |

**Official SDKs:**
- TypeScript: `@agentclientprotocol/sdk` (npm)
- Python: `python-sdk`
- Rust: `agent-client-protocol` (crates.io)
- Kotlin: `acp-kotlin` (JVM)
- Java: `java-sdk`

---

## 3. Architecture

### Roles

- **Client** = code editor/IDE (Zed, JetBrains, Neovim, Emacs, etc.)
- **Agent** = AI coding agent (Claude Code, Gemini CLI, Codex CLI, Goose, etc.)

### Connection Model

Editor spawns agent as a **sub-process on demand**. Communication via **stdin/stdout using JSON-RPC 2.0**. A single connection supports concurrent sessions.

```
┌─────────────┐  JSON-RPC 2.0  ┌─────────────┐  MCP  ┌─────────────┐
│   Editor     │◄──── stdio ───►│   Agent      │◄────►│ MCP Servers  │
│  (ACP Client)│                │ (ACP Server) │      │ (Tools/Data) │
└─────────────┘                └─────────────┘      └─────────────┘
```

### Deployment Modes

1. **Local agents** (primary): subprocess, JSON-RPC over stdio
2. **Remote agents** (WIP): cloud-hosted, HTTP or WebSocket transport

### Design Principles

1. JSON-RPC foundation reusing MCP types where possible
2. UX-centric flexibility — live progress visualization (token-by-token streaming)
3. Trust model assuming local file access within a trusted editor environment
4. Bidirectional communication — both editor and agent can initiate requests
5. Capability negotiation for extensibility without breaking changes
6. Markdown for all human-readable text content

---

## 4. Protocol Lifecycle

### Phase 1 — Initialization

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": 1,
    "capabilities": {}
  }
}
```

Client sends `initialize` with `protocolVersion` and `clientCapabilities`. Agent responds with `protocolVersion` and `agentCapabilities`. Optionally followed by `authenticate`.

### Phase 2 — Session Establishment

Client creates a session via `session/new` or resumes via `session/load` (if `loadSession` capability supported). Agent generates a unique `session_id` (UUID). Sessions can specify MCP servers and working directory.

### Phase 3 — Prompt Turn

1. Client sends `session/prompt` with user input
2. Agent streams `session/update` notifications for real-time progress
3. Agent may request file operations or permissions from the client
4. Client can interrupt with `session/cancel` notification
5. Agent concludes with a `session/prompt` response including stop reason

---

## 5. Message Format

All messages conform to **JSON-RPC 2.0**:

| Type | Has `id` | Direction | Description |
|------|----------|-----------|-------------|
| Request | Yes | Bidirectional | Expects response |
| Response | Yes (echo) | Reply | `result` or `error` |
| Notification | No | Bidirectional | One-way, no response |

Messages are **UTF-8 encoded**, delimited by **newlines** (`\n`), must not contain embedded newlines. Agents must not write non-ACP output to stdout (diagnostics go to stderr).

---

## 6. Protocol Methods

### Agent Methods (client → agent)

| Method | Required | Purpose |
|--------|----------|---------|
| `initialize` | Yes | Version/capability negotiation |
| `authenticate` | Yes | Agent authentication |
| `session/new` | Yes | Create conversation session |
| `session/prompt` | Yes | Receive user messages |
| `session/load` | Optional | Resume previous sessions |
| `session/set_mode` | Optional | Switch operating modes |
| `session/cancel` | Notification | Interrupt processing |

### Client Methods (agent → client)

| Method | Required | Purpose |
|--------|----------|---------|
| `session/request_permission` | Yes | Authorize tool execution |
| `fs/read_text_file` | Optional | Read file from filesystem |
| `fs/write_text_file` | Optional | Write file to filesystem |
| `terminal/create` | Optional | Create terminal, execute command |
| `terminal/output` | Optional | Terminal output streaming |
| `terminal/wait_for_exit` | Optional | Wait for command completion |
| `terminal/kill` | Optional | Kill running command |
| `terminal/release` | Optional | Release terminal resources |

### Client Notifications (agent → client)

| Method | Purpose |
|--------|---------|
| `session/update` | Streams real-time updates: message chunks, tool calls, plan updates, mode changes |

---

## 7. Content Blocks

Multi-modal content support:

| Block Type | Description | Requires Capability |
|------------|-------------|-------------------|
| `TextContentBlock` | Text/Markdown | — (required) |
| `ImageContentBlock` | Base64-encoded images | `image` |
| `AudioContentBlock` | Audio data with MIME type | `audio` |
| `ResourceLinkBlock` | References to resources | — (required) |
| `Resource` | Embedded resource contents | `embeddedContext` |

All content blocks support optional **Annotations** for audience, priority, and modification metadata.

---

## 8. Tool Calls and Permissions

### Tool Call Lifecycle

1. `start_tool_call()` — initiate with parameters
2. `update_tool_call()` — progress updates / intermediate results
3. `tool_content()` — final tool output

### Permission Model

Agent requests permission via `session/request_permission`. User options:
- `allow_once` — permit this one invocation
- `allow_always` — auto-permit future invocations of same type
- `reject_once` — deny this one
- `reject_always` — auto-deny future invocations of same type

---

## 9. Plans

Agents can communicate structured plans:

```typescript
interface Plan {
  entries: PlanEntry[];
}

interface PlanEntry {
  content: string;           // Human-readable task description
  status: 'pending' | 'in_progress' | 'completed';
  priority: 'high' | 'medium' | 'low';
}
```

---

## 10. Capabilities Negotiation

During `initialize`:
- Client advertises: `fs.readTextFile`, `fs.writeTextFile`, `terminal` support
- Agent advertises: `loadSession`, `promptCapabilities.audio`, MCP transport support

**Protocol version**: single integer (MAJOR only, uint16). Client proposes; agent confirms or counter-proposes. Incompatible → client closes connection.

New capabilities are **not** breaking changes — omitted capabilities simply indicate non-support.

---

## 11. Extensibility

- All types include `_meta` field (`{ [key: string]: unknown }`) for custom data
- Method names starting with `_` reserved for custom extensions (e.g., `_zed.dev/workspace/buffers`)
- Reserved `_meta` root keys: `traceparent`, `tracestate`, `baggage` (W3C trace context)
- Unrecognized custom requests → `-32601 Method not found`
- Unrecognized notifications → silently ignored
- Default 50MB buffer limit for multimodal data

---

## 12. Key Constraints

- All file paths **MUST** be absolute
- Line numbers use **1-based indexing**
- Markdown is default format for user-readable content
- Agents must not write non-ACP data to stdout (stderr only for diagnostics)

---

## 13. ACP vs MCP Comparison

ACP and MCP are **complementary, not competing**:

| Aspect | ACP (Agent Client Protocol) | MCP (Model Context Protocol) |
|--------|---------------------------|------------------------------|
| **Purpose** | Editor ↔ Agent communication | Agent ↔ Tool/Data communication |
| **Created by** | Zed Industries | Anthropic |
| **Layer** | User interface (horizontal) | Capability (vertical) |
| **Transport** | JSON-RPC over stdio (subprocess) | JSON-RPC over stdio or HTTP+SSE |
| **Focus** | UI rendering, sessions, permissions | Tool invocation, resource access |

**Together**: Editor (ACP client) → launches Agent (ACP server) → connects to MCP servers for tools/data. ACP reuses MCP types for content representation. Sessions can specify MCP servers to use.

---

## 14. Adoption Status (April 2026)

### Editor Support

| Editor | Status |
|--------|--------|
| Zed | Native (creator, reference implementation) |
| JetBrains IDEs | Full integration + ACP Agent Registry (January 2026) |
| Neovim | Via CodeCompanion, avante.nvim plugins |
| Emacs | Via agent-shell plugin |
| Obsidian | Side panel integration |
| marimo | Python notebook environment |
| Kiro (AWS) | CLI-based support |
| Eclipse | Prototype |

### Agent Support

| Agent | Status |
|-------|--------|
| Claude Code (Anthropic) | Supported via SDK adapter |
| Gemini CLI (Google) | Original reference implementation |
| Codex CLI (OpenAI) | Supported |
| GitHub Copilot | Public preview |
| Cursor | Available in JetBrains via ACP (March 2026) |
| Goose (Block/Square) | Supported (open-source) |
| Cline, OpenHands | Community support |
| Augment Code, Qwen Code, Kimi CLI | Supported |
| Aider | In development |

### Milestones

- **August 2025**: ACP launched by Zed Industries
- **January 2026**: JetBrains ACP Agent Registry goes live
- **March 2026**: Cursor available in JetBrains via ACP

---

## 15. Relevance to ADP

ADP currently implements **MCP** (Model Context Protocol) in `libs/McpServer/` for AI assistant integration. ACP operates at a different layer — connecting editors to agents, while MCP connects agents to tools/data.

**Potential relevance**: If ADP wanted to expose debugging data directly to editor-embedded agents (not just via MCP tools), ACP could be a future integration point. However, MCP remains the primary protocol for tool/data access, which is ADP's current use case.

The MCP server in ADP (`libs/McpServer/`) already enables AI agents (including those connected via ACP to editors) to access debug data through MCP tools — so the current architecture already works well with ACP-connected agents indirectly.
