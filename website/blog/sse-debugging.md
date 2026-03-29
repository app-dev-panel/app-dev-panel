---
title: Real-time Debugging with Server-Sent Events
date: 2026-03-25
author: ADP Team
tags: [deep-dive, sse, architecture]
---

<script setup>
import BlogPost from '../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="Real-time Debugging with Server-Sent Events"
  date="2026-03-25"
  author="ADP Team"
  :tags="['deep-dive', 'sse', 'architecture']"
  readingTime="6 min"
/>

Traditional PHP debugging tools require you to refresh the page to see new data. You trigger a request, switch to the profiler, hit reload, find the latest entry, and open it. Repeat dozens of times per debugging session. ADP eliminates this friction with Server-Sent Events (SSE) — debug data streams to the panel the moment it is collected.

This post explains how the SSE system works under the hood and why we chose it over alternatives.

## Why SSE Over WebSockets?

When we designed ADP's real-time layer, we considered three options: polling, WebSockets, and SSE. Each has trade-offs.

**Polling** is the simplest approach — the frontend periodically asks the server for new data. It works everywhere but introduces latency (you only see new data at the poll interval) and generates unnecessary traffic when nothing has changed.

**WebSockets** provide full-duplex communication. The frontend and server can send messages in both directions at any time. This is powerful but comes with complexity: WebSocket connections require a persistent server process, complicate deployment behind reverse proxies, and need explicit reconnection logic.

**SSE** sits in the middle. The server pushes events to the client over a standard HTTP connection. It is unidirectional (server to client only), which is exactly what a debugging panel needs — the server has new debug entries; the client needs to know about them. SSE connections automatically reconnect on failure, work through standard HTTP infrastructure, and require no special server setup.

For a debugging tool, SSE is the right choice. We do not need the client to send data to the server in real time. We need the server to notify the client when new debug data arrives. SSE does this with minimal complexity.

## Architecture Overview

ADP's SSE implementation has three components:

1. **Storage events** — When the debugger flushes collector data to storage, it emits an event signaling that new data is available.
2. **SSE endpoint** — An HTTP endpoint that holds the connection open and streams events to the client as they occur.
3. **Frontend listener** — A JavaScript `EventSource` that receives events and updates the Redux store.

The flow is:

```
Target App → Debugger Flush → Storage Write → SSE Event
                                                  ↓
                                          SSE Endpoint
                                                  ↓
                                          EventSource (Browser)
                                                  ↓
                                          Redux Store Update
                                                  ↓
                                          UI Re-render
```

## The SSE Endpoint

The API module exposes an SSE endpoint at `/debug/stream`. When the frontend connects, the server holds the connection open and sends events using the standard SSE format:

```
event: debug.entry
data: {"id":"abc123","url":"/api/users","method":"GET","status":200,"time":1.23}

event: debug.entry
data: {"id":"def456","url":"/api/orders","method":"POST","status":201,"time":0.87}
```

Each event contains the metadata for a new debug entry — enough for the list view to render without fetching the full payload. When the user clicks on an entry, the frontend fetches the complete data via a REST call.

This approach keeps the SSE stream lightweight. We only push entry summaries (typically under 1 KB each), not the full collector data which can be much larger.

## Frontend Integration

On the frontend, the connection is managed through a standard `EventSource`:

```typescript
const source = new EventSource('/debug/stream');

source.addEventListener('debug.entry', (event: MessageEvent) => {
  const entry = JSON.parse(event.data);
  dispatch(addDebugEntry(entry));
});

source.addEventListener('error', () => {
  // EventSource automatically reconnects.
  // We update UI state to show the reconnection status.
  dispatch(setConnectionStatus('reconnecting'));
});

source.addEventListener('open', () => {
  dispatch(setConnectionStatus('connected'));
});
```

The `EventSource` API handles reconnection automatically. If the connection drops — due to a network hiccup, server restart, or deployment — the browser reconnects and resumes receiving events. The server can optionally send a `Last-Event-ID` to allow the client to catch up on missed events.

## Handling Multiple Applications

ADP can monitor multiple applications simultaneously. Each application writes to its own storage namespace. The SSE endpoint accepts a filter parameter to subscribe to events from specific applications:

```
GET /debug/stream?app=my-api
GET /debug/stream?app=my-worker
GET /debug/stream              # all applications
```

The panel UI lets you switch between applications or view all entries in a unified timeline, which is particularly useful when debugging distributed systems where a frontend request triggers API calls that spawn background jobs.

## Console Commands and Queue Workers

SSE is especially valuable for debugging console commands and queue workers. These processes do not generate HTTP responses that a traditional profiler can intercept. With ADP, the debugger flushes data at the end of each command or job execution, and the SSE stream delivers it to the panel immediately.

This means you can watch queue worker processing in real time — see each job's database queries, log messages, and execution time as they happen.

## Performance Considerations

SSE connections are long-lived HTTP connections. Each connected browser tab holds one open connection. For a development tool, this is not a concern — you typically have one or two panel tabs open. But we took steps to minimize resource usage:

- **Event buffering** — Events are batched when multiple debug entries arrive in quick succession, reducing the number of individual messages.
- **Heartbeat** — The server sends a comment line (`: heartbeat`) every 30 seconds to keep the connection alive through proxies and load balancers.
- **Graceful shutdown** — When the debug server stops, it sends a close event so the frontend can show an appropriate status instead of endlessly reconnecting.

## Try It Out

If you have ADP installed, the SSE connection is active by default. Open the panel, trigger some requests in your application, and watch the entries appear in real time. No configuration needed.

For applications running console commands or queue workers, make sure the ADP debug server is running alongside your application. The CLI module provides a simple command to start it:

```bash
php vendor/bin/adp serve
```

In the next post, we will explore how ADP integrates with AI assistants through the MCP (Model Context Protocol) server — bringing intelligent analysis to your debug data.
