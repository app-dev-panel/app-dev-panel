---
title: llms.txt — Documentation for AI Agents
---

# llms.txt

ADP provides machine-readable documentation following the [llms.txt](https://llmstxt.org/) standard. This allows AI assistants (Claude, ChatGPT, Cursor, Copilot, and others) to quickly understand the project and provide accurate answers about it.

## What is llms.txt?

llms.txt is an open standard proposed by Jeremy Howard (fast.ai). It provides a well-known URL (`/llms.txt`) where websites expose their documentation in a clean markdown format optimized for LLM consumption — no HTML, no JavaScript, no navigation clutter.

## Available Files

| File | URL | Content |
|------|-----|---------|
| `llms.txt` | [/llms.txt](/llms.txt) | Concise table of contents with links to each documentation page |
| `llms-full.txt` | [/llms-full.txt](/llms-full.txt) | All documentation pages concatenated into a single file |

**`llms.txt`** is a lightweight index — section headings and links. Use it when your AI tool has limited context or you need a quick overview.

**`llms-full.txt`** contains the complete documentation in one file. Use it when your AI tool has a large context window and you want comprehensive answers.

## How to Use

### In Claude (claude.ai)

Paste the URL in the chat:

```
Read https://app-dev-panel.github.io/app-dev-panel/llms-full.txt and answer my questions about ADP.
```

### In Claude Code

Use the `WebFetch` tool or paste the URL:

```
Fetch https://app-dev-panel.github.io/app-dev-panel/llms-full.txt
```

### In Cursor / Copilot / other IDE assistants

Add the URL as a documentation source in your IDE settings, or paste it into the chat context.

### In custom agents

Fetch the file programmatically and include it in the system prompt:

```python
import httpx

response = httpx.get("https://app-dev-panel.github.io/app-dev-panel/llms-full.txt")
docs = response.text

messages = [
    {"role": "system", "content": f"ADP documentation:\n\n{docs}"},
    {"role": "user", "content": "How do I add a custom collector?"},
]
```

## What's Inside

Both files are auto-generated at build time from the same markdown sources that produce this documentation site. They include:

- **Getting Started** — installation for each framework
- **Architecture** — layers, data flow, proxy system
- **Collectors** — all 28 collectors with descriptions
- **API Reference** — REST endpoints, SSE, Inspector API
- **Adapters** — Symfony, Laravel, Yii 3, Yii 2, Cycle ORM
- **MCP Server** — AI integration setup
- **CLI** — commands and options

Content stays in sync automatically — when a documentation page is updated, the next build regenerates both files.

## How It Works

The generation pipeline is a VitePress `buildEnd` hook:

1. Reads the sidebar configuration from `config.ts`
2. For `llms.txt` — outputs section headings and links
3. For `llms-full.txt` — reads each linked markdown file, strips frontmatter and Vue components, concatenates with `---` separators

Only English pages are included. Russian translations are not part of the llms.txt output.

Source: [`website/.vitepress/plugins/llms-txt.ts`](https://github.com/app-dev-panel/app-dev-panel/blob/master/website/.vitepress/plugins/llms-txt.ts)
