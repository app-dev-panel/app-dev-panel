---
title: llms.txt — Documentation for AI Agents
description: "ADP provides machine-readable docs via llms.txt standard for AI assistants like Claude, ChatGPT, and Copilot."
---

# llms.txt

ADP provides machine-readable documentation following the [llms.txt](https://llmstxt.org/) standard. This allows AI assistants (Claude, ChatGPT, Cursor, Copilot, and others) to quickly understand the project and provide accurate answers about it.

## What is llms.txt?

llms.txt is an open standard proposed by Jeremy Howard (fast.ai). It provides a well-known URL (`/llms.txt`) where websites expose their documentation in a clean markdown format optimized for LLM consumption — no HTML, no JavaScript, no navigation clutter.

## Available Files

| File | URL | Content |
|------|-----|---------|
| `llms.txt` | [/llms.txt](/llms.txt) | Concise table of contents with links to per-page `.md` files |
| `llms-full.txt` | [/llms-full.txt](/llms-full.txt) | All documentation pages concatenated into a single file |
| `*.md` (per-page) | e.g. [/guide/collectors.md](/guide/collectors.md) | Clean markdown for any individual page |

**`llms.txt`** is a lightweight index — titles and links. Use it when your AI tool has limited context or you need a quick overview.

**`llms-full.txt`** contains the complete documentation in one file (~13K tokens). Use it when your AI tool has a large context window and you want comprehensive answers.

**Per-page `.md` files** are available for every documentation page. Use them when you need information about a specific topic without loading the entire documentation.

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
- **Collectors** — all 30 collectors with descriptions
- **API Reference** — REST endpoints, SSE, Inspector API
- **Adapters** — Symfony, Yii 2, Yii 3, Laravel
- **MCP Server** — AI integration setup
- **CLI** — commands and options

Content stays in sync automatically — when a documentation page is updated, the next build regenerates both files.

## How It Works

Powered by [`vitepress-plugin-llms`](https://github.com/okineadev/vitepress-plugin-llms) — the same plugin used by Vite, Vue.js, and Vitest.

The plugin operates as a Vite plugin during the build:

1. **Collects** all markdown files during Vite's transform phase
2. **Cleans** content via remark AST — strips frontmatter, HTML, Vue components
3. **Generates** `llms.txt` (TOC), `llms-full.txt` (concatenated), and per-page `.md` files
4. **Injects** hidden hints on HTML pages pointing LLMs to the `.md` versions

Only English pages are included. Russian translations are excluded via `ignoreFiles: ['ru/**']`.

Source: [plugin config in `config.ts`](https://github.com/app-dev-panel/app-dev-panel/blob/master/website/.vitepress/config.ts)
