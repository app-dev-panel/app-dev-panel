---
title: AI-Powered Debugging with MCP Server
date: 2026-03-27
author: ADP Team
tags: [ai, mcp, deep-dive]
---

<script setup>
import BlogPost from '../../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="AI-Powered Debugging with MCP Server"
  date="2026-03-27"
  author="ADP Team"
  :tags="['ai', 'mcp', 'deep-dive']"
  readingTime="7 min"
/>

Debugging is fundamentally about understanding what your application did and why. You collect data — logs, queries, request traces, exceptions — and then analyze it to find the root cause. What if an AI assistant could do that analysis for you, with full access to the same debug data you see in the panel?

ADP's MCP Server makes this possible. It exposes your debug data to AI assistants through the Model Context Protocol, enabling them to query, analyze, and reason about your application's runtime behavior.

## What is MCP?

The **Model Context Protocol (MCP)** is an open standard for connecting AI assistants to external data sources and tools. Instead of copying debug output into a chat window, MCP lets the AI assistant connect directly to ADP and pull the data it needs.

MCP defines three core concepts:

- **Tools** — Functions the AI can call (e.g., "list recent debug entries", "get exception details")
- **Resources** — Data the AI can read (e.g., "the full collector data for entry X")
- **Prompts** — Pre-defined templates for common analysis tasks

ADP implements all three, giving AI assistants deep access to your debug data.

## How It Works

ADP's MCP Server supports two transport modes:

### Stdio Transport

The stdio transport runs as a subprocess of the AI assistant. The assistant launches the MCP server, sends JSON-RPC messages via stdin, and reads responses from stdout. This is the simplest setup — no network configuration needed.

```bash
# The AI assistant launches this process
php vendor/bin/adp mcp:serve --transport=stdio
```

This mode works with AI tools like Claude Code that support local MCP servers. You add ADP to your MCP configuration, and the assistant can immediately start querying debug data.

### HTTP Transport

The HTTP transport runs as a standalone server, accepting MCP requests over HTTP. This is useful when the AI assistant runs remotely or when you want multiple assistants to access the same debug data.

```bash
php vendor/bin/adp mcp:serve --transport=http --port=8050
```

The HTTP transport uses the standard MCP Streamable HTTP protocol, making it compatible with any MCP client.

## Available Tools

ADP exposes a rich set of tools that AI assistants can use to explore debug data:

### Listing and Filtering

- **`list_debug_entries`** — Returns recent debug entries with metadata (URL, method, status, timestamp). Supports filtering by time range, status code, and application.
- **`search_entries`** — Full-text search across all collected data. Find entries containing specific error messages, SQL queries, or log patterns.

### Deep Inspection

- **`get_entry_detail`** — Returns the complete collector data for a specific debug entry, including all logs, queries, events, and HTTP details.
- **`get_exceptions`** — Extracts exception data with stack traces, grouped by type and frequency.
- **`get_slow_queries`** — Lists database queries exceeding a configurable threshold, with SQL, bindings, and execution time.

### Analysis

- **`compare_entries`** — Compares two debug entries side by side, highlighting differences in timing, queries, and log output. Useful for regression debugging.
- **`get_timeline`** — Returns a chronological view of all events within a request, showing the execution flow across middleware, services, and database calls.

## Real-World Usage

Here is what working with ADP's MCP integration looks like in practice.

### Scenario: Debugging a Slow Endpoint

You notice that `/api/orders` is taking 3 seconds to respond. Instead of manually digging through the debug panel, you ask your AI assistant:

> "The /api/orders endpoint is slow. Look at the recent debug entries and tell me why."

The assistant calls `list_debug_entries` filtered by URL, finds the slow entries, calls `get_entry_detail` on the worst one, and analyzes the data. It might respond:

> "The /api/orders endpoint made 47 database queries in the last request, with an N+1 pattern on the OrderItem relation. The `findAllWithItems` method issues one query per order instead of eager loading. The total query time was 2.8 seconds out of the 3.1 second response time."

### Scenario: Recurring Exceptions

You are getting intermittent 500 errors in production. You ask:

> "Show me the exceptions from the last hour and identify any patterns."

The assistant calls `get_exceptions`, groups them by type and message, and reports:

> "There were 12 exceptions in the last hour, all `ConnectionTimeoutException` from the Redis client. They cluster around 14:32 and 14:47, suggesting intermittent Redis connectivity issues rather than a code bug. The affected endpoints are /api/sessions and /api/cart, both of which use the session cache."

## Configuration

To enable the MCP server, no additional packages are needed — it is included in the core ADP installation. Configure it in your ADP settings:

```php
return [
    'mcp' => [
        'enabled' => true,
        'transport' => 'stdio', // or 'http'
        'tools' => [
            'slow_query_threshold' => 100, // milliseconds
            'max_entries' => 50,
        ],
    ],
];
```

For Claude Code, add ADP to your MCP configuration file:

```json
{
  "mcpServers": {
    "adp": {
      "command": "php",
      "args": ["vendor/bin/adp", "mcp:serve", "--transport=stdio"]
    }
  }
}
```

## Privacy and Security

Debug data can contain sensitive information — request bodies, database contents, authentication tokens. ADP's MCP Server respects the same data filtering rules as the rest of the panel. Sensitive fields are redacted before being exposed through MCP tools. You can configure additional redaction patterns for your domain:

```php
'mcp' => [
    'redact' => [
        'headers' => ['Authorization', 'Cookie', 'X-API-Key'],
        'body_fields' => ['password', 'credit_card', 'ssn'],
    ],
],
```

The MCP server is designed for development environments only. It should never be exposed in production.

## Looking Ahead

MCP integration opens up possibilities beyond simple data retrieval. We are exploring:

- **Automated root cause analysis** — Pre-built prompts that guide the AI through systematic debugging workflows
- **Performance regression detection** — Comparing debug entries across deployments to catch slowdowns
- **Code fix suggestions** — Using debug data combined with source code context to suggest targeted fixes

The combination of structured debug data and AI reasoning is a powerful debugging workflow. ADP's MCP Server brings them together in a standard, interoperable way.
