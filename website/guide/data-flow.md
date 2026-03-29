---
title: Data Flow
---

# Data Flow

This page describes how debug data flows from your application to the ADP panel.

## Overview

```
Target App → Adapter → Proxies → Collectors → Debugger → Storage → API → Frontend
```

## Step-by-Step Flow

### 1. Adapter Installation

Your application runs with an ADP adapter installed (e.g., Symfony adapter, Laravel adapter). The adapter registers itself in the framework's dependency injection container during bootstrap.

### 2. Proxy Registration

The adapter registers **proxies** that wrap PSR interfaces in the DI container. For example, `LoggerInterfaceProxy` wraps your PSR-3 logger. Your application code continues to use the standard interfaces -- it is completely unaware of the interception.

### 3. Data Collection

During request processing, proxies intercept calls and feed data to **collectors**:

- Logger calls go to `LogCollector`
- Dispatched events go to `EventCollector`
- HTTP client requests go to `HttpClientCollector`
- Database queries go to `DatabaseCollector` (via adapter hooks)

Each collector accumulates data in memory for the duration of the request.

### 4. Debugger Flush

When the request completes (or a console command finishes), the **Debugger** triggers shutdown:

1. Calls `shutdown()` on all collectors
2. Calls `getCollected()` to retrieve accumulated data
3. Serializes objects using `Dumper` (with depth control and circular reference detection)
4. Calls `flush()` on storage

### 5. Storage Persistence

**FileStorage** writes three JSON files per debug entry:

| File | Contents |
|------|----------|
| `{id}.summary.json` | Entry metadata (timestamp, URL, status, collectors) |
| `{id}.data.json` | Full collector payloads |
| `{id}.objects.json` | Serialized PHP objects |

### 6. Frontend Display

The **API** serves stored data via REST endpoints. The frontend uses **SSE** (Server-Sent Events) to detect new entries in real-time:

1. Frontend subscribes to `/debug/api/event-stream`
2. API polls storage every second, computing an MD5 hash of summaries
3. When a new entry appears, the hash changes and an event is emitted
4. Frontend fetches the updated entry list and displays it

## External Applications

Non-PHP applications can send debug data via the **Ingestion API** (`POST /debug/api/ingest`), bypassing the proxy/collector pipeline entirely. Data is written directly to storage.
