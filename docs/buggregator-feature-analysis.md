# Buggregator Feature Analysis & Integration Plan for ADP

## Overview

[Buggregator](https://github.com/buggregator/server) is a multi-purpose debugging server for PHP (and other languages).
It aggregates logs, dumps, errors, profiling data, emails, and HTTP requests into a unified web UI.

This document analyzes Buggregator's features and maps them to ADP, identifying what ADP already has,
what's missing, and how to integrate the missing capabilities.

## Architecture Comparison

| Aspect | Buggregator | ADP |
|--------|-------------|-----|
| **Backend** | Spiral Framework + RoadRunner | PSR-based, framework-agnostic Kernel |
| **Frontend** | Vue 3 + TailwindCSS | React 18 + MUI 5 + Redux Toolkit |
| **Real-time** | WebSockets (Centrifugo) | SSE + UDP broadcast |
| **Storage** | MySQL/PostgreSQL/SQLite (CycleORM) | JSON files with GC |
| **Transport** | HTTP + TCP (multi-protocol) | HTTP REST + SSE + UDP Unix sockets |
| **Deployment** | Docker container | Embedded in app (adapter) + standalone server |
| **Data model** | Flat events with type + JSON payload | Structured collectors per debug entry |

### Key Architectural Difference

Buggregator is a **standalone server** that receives data from external apps via protocols (Sentry, VarDumper TCP, Monolog TCP, SMTP, HTTP).
ADP is an **embedded debugger** that hooks into the app via adapters/proxies, collecting data from inside the runtime.

This means ADP has **deeper introspection** (DI container, middleware, routes, events, services) while Buggregator has **broader protocol compatibility** (Sentry, Ray, Inspector, SMTP server).

---

## Feature-by-Feature Comparison

### 1. Sentry Protocol Compatibility ❌ ADP Missing

**Buggregator**: Accepts Sentry SDK error reports (envelope + store endpoints). Apps configured with Sentry SDK can send errors to Buggregator instead.

**ADP impact**: HIGH. This would let any app with Sentry SDK installed send errors to ADP without a framework adapter.

**Implementation plan**:
- Add Sentry ingestion endpoints to `libs/API`: `POST /api/{projectId}/envelope/` and `POST /api/{projectId}/store/`
- Parse Sentry envelope format (gzip-compressed, chunked JSON)
- Map Sentry events to ADP's `ExceptionCollector` data format
- Store as a new event type in the ingestion API
- Frontend: render Sentry-style error pages (stack traces, breadcrumbs, tags, user context)

**Effort**: Medium. ~3-5 days backend + 2-3 days frontend.

**Files to create/modify**:
- `libs/API/src/Http/Handler/Sentry/EnvelopeHandler.php` (new)
- `libs/API/src/Http/Handler/Sentry/StoreHandler.php` (new)
- `libs/API/src/Http/Handler/Sentry/PayloadParser.php` (new)
- `libs/Kernel/src/Collector/SentryCollector.php` (new) — or use ingestion API
- Frontend: new Sentry event page component

---

### 2. Spatie Ray Integration ❌ ADP Missing

**Buggregator**: Accepts Ray debug tool requests. Developers use `ray($var)` in code, data appears in Buggregator.

**ADP impact**: MEDIUM. Ray is popular in Laravel ecosystem. Would broaden ADP's user base.

**Implementation plan**:
- Add Ray-compatible HTTP endpoints to `libs/API`
- Handle Ray payload format (multi-part merge, lock management)
- Map Ray dumps to ADP's `VarDumperCollector` format
- Support Ray-specific features: colors, labels, screen clearing

**Effort**: Small. ~2 days backend, 1 day frontend.

**Files to create/modify**:
- `libs/API/src/Http/Handler/Ray/EventHandler.php` (new)
- `libs/API/src/Http/Handler/Ray/AvailabilityHandler.php` (new)

---

### 3. Inspector Protocol ❌ ADP Missing

**Buggregator**: Accepts [Inspector](https://www.inspector.dev/) monitoring data (transactions, processes, requests).

**ADP impact**: LOW-MEDIUM. Inspector is a niche APM tool. Useful for users already using Inspector.

**Implementation plan**:
- Add Inspector-compatible HTTP endpoint
- Parse base64-encoded JSON payloads
- Validate via `X-Inspector-Key` header
- Map to ADP's timeline/request collector format

**Effort**: Small. ~1-2 days.

---

### 4. SMTP Server (Email Catcher) ❌ ADP Missing

**Buggregator**: Runs a fake SMTP server on port 1025. Apps send emails there; Buggregator captures and displays them with HTML preview, attachments, headers.

**ADP impact**: HIGH. Email testing is a universal need. ADP has `MailerCollector` that captures mailer events, but no standalone SMTP server.

**Implementation plan**:

Option A — **Embedded SMTP in CLI server** (recommended):
- Add SMTP protocol handler to `libs/Cli` debug server
- Parse SMTP protocol (EHLO, MAIL FROM, RCPT TO, DATA)
- Extract email message (headers, HTML/text body, attachments)
- Store via ingestion API or direct storage
- Frontend: email preview page with HTML rendering, attachment downloads

Option B — **PSR-based SMTP proxy**:
- Create SMTP proxy in Kernel that wraps framework mailer transport
- Intercept outgoing emails before they're sent
- Already partially done via `MailerCollector` — extend with full message capture

**Effort**: Medium. ~3-4 days backend (SMTP parsing), 2 days frontend (email preview UI).

**Files to create/modify**:
- `libs/Cli/src/Smtp/SmtpServer.php` (new)
- `libs/Cli/src/Smtp/SmtpParser.php` (new)
- `libs/Kernel/src/Collector/SmtpCollector.php` (new)
- Frontend: `SmtpPage` with email preview, attachment list

---

### 5. XHProf Profiler UI ❌ ADP Missing (partially)

**Buggregator**: Full XHProf profiler with flame charts, call graphs, top functions, CPU/memory/wall-time metrics, edge diffs.

**ADP**: Has `TimelineCollector` for basic timing data but no flame graph or XHProf integration.

**ADP impact**: HIGH. Performance profiling is essential for debugging.

**Implementation plan**:
- Add XHProf data ingestion endpoint to `libs/API`
- Parse XHProf output format (caller=>callee => {ct, wt, cpu, mu, pmu})
- Build call tree, calculate peaks, diffs, percentages (reference: Buggregator's `ProfileBuilder`)
- Store profile data with edges
- Frontend: flame chart visualization, call graph table, top functions list

**Effort**: Large. ~5-7 days backend, 5-7 days frontend (flame chart is complex).

**Files to create/modify**:
- `libs/API/src/Http/Handler/Profiler/` (new directory)
- `libs/Kernel/src/Collector/ProfilerCollector.php` (new)
- `libs/Kernel/src/Profiler/ProfileBuilder.php` (new)
- `libs/Kernel/src/Profiler/Edge.php`, `Cost.php`, `Peaks.php` (new)
- Frontend: `ProfilerPage`, `FlameChart`, `CallGraph`, `TopFunctions` components

---

### 6. HTTP Request Dump Server ❌ ADP Missing

**Buggregator**: Standalone HTTP endpoint that captures ANY HTTP request sent to it (method, headers, body, query, files). Useful for webhook testing.

**ADP**: Has `RequestCollector` but it only captures the app's own incoming requests via middleware.

**ADP impact**: MEDIUM. Useful for testing webhooks, API callbacks, third-party integrations.

**Implementation plan**:
- Add catch-all HTTP dump endpoint to `libs/API`: `POST /debug/api/dump`
- Store full request data (method, URI, headers, body, query params, uploaded files)
- Frontend: HTTP dump list with request detail view

**Effort**: Small. ~1-2 days backend, 1 day frontend.

**Files to create/modify**:
- `libs/API/src/Http/Handler/HttpDump/CaptureHandler.php` (new)
- Frontend: `HttpDumpPage` component

---

### 7. Monolog TCP Server ❌ ADP Missing (partially)

**Buggregator**: TCP server that accepts Monolog JSON log messages directly (no framework integration needed).

**ADP**: Has `LogCollector` via PSR-3 proxy. Logs are collected through the adapter, not externally.

**ADP impact**: MEDIUM. Would allow non-PHP or standalone apps to send logs to ADP.

**Implementation plan**:
- Extend `libs/Cli` debug server to accept TCP log messages
- Parse Monolog JSON format (message, context, level, channel, datetime)
- Store via ingestion API
- Already supported via HTTP ingestion API (`POST /debug/api/ingest/log`) — just needs TCP transport

**Effort**: Small. ~1-2 days (TCP transport layer in CLI server).

---

### 8. Webhooks (Outbound) ❌ ADP Missing

**Buggregator**: Event-driven webhook system. When events are received, trigger HTTP webhooks to external systems (Slack, Discord, custom URLs). Includes delivery tracking and retry logic.

**ADP impact**: MEDIUM. Useful for CI/CD integration, alerting, team notifications.

**Implementation plan**:
- Add webhook configuration (YAML + API)
- Event listener that triggers HTTP requests on new debug entries
- Delivery tracking with retry (exponential backoff)
- Frontend: webhook management page

**Effort**: Medium. ~3-4 days backend, 2 days frontend.

**Files to create/modify**:
- `libs/Kernel/src/Webhook/WebhookManager.php` (new)
- `libs/Kernel/src/Webhook/Webhook.php` (new)
- `libs/Kernel/src/Webhook/Delivery.php` (new)
- `libs/API/src/Http/Handler/Webhook/` (new)
- Frontend: `WebhooksSettingsPage`

---

### 9. Projects / Multi-Project Support ❌ ADP Missing (partially)

**Buggregator**: Full project management — events are tagged with a project key. Projects defined via YAML or database.

**ADP**: Has `ServiceRegistry` for multi-app support, but no project-level organization of debug data.

**ADP impact**: LOW-MEDIUM. ADP's service registry already handles multi-app. Adding project tagging would improve organization.

**Implementation plan**:
- Add optional project/tag field to debug entries
- Filter events by project in API and frontend
- Minimal — build on existing ServiceRegistry

**Effort**: Small. ~1-2 days.

---

### 10. Auth0 / Authentication ❌ ADP Missing

**Buggregator**: Auth0 integration with JWT, user profiles, login page.

**ADP**: IP-based filtering + optional token auth only.

**ADP impact**: LOW for local dev, HIGH for shared/cloud deployments.

**Implementation plan**:
- Add pluggable auth middleware to `libs/API`
- Support JWT validation (any OIDC provider, not just Auth0)
- Frontend: login page, user profile

**Effort**: Medium. ~3-4 days.

---

### 11. Metrics Collection ❌ ADP Missing

**Buggregator**: RoadRunner metrics (counters with labels) for tracking event volumes.

**ADP impact**: LOW. Nice-to-have for monitoring ADP itself.

**Implementation plan**:
- Add simple counter metrics to API endpoints
- Expose via `/metrics` endpoint (Prometheus format)

**Effort**: Small. ~1 day.

---

## Features ADP Already Has (Buggregator Lacks)

| Feature | ADP | Buggregator |
|---------|:---:|:-----------:|
| DI Container inspection | ✅ | ❌ |
| Middleware pipeline view | ✅ | ❌ |
| Route matching/testing | ✅ | ❌ |
| Database schema browser | ✅ | ❌ |
| File explorer | ✅ | ❌ |
| Git operations (checkout, pull) | ✅ | ❌ |
| Composer management | ✅ | ❌ |
| Cache management | ✅ | ❌ |
| OPcache inspection | ✅ | ❌ |
| Test/analysis runners | ✅ | ❌ |
| Code generation (Gii) | ✅ | ❌ |
| OpenAPI/Swagger viewer | ✅ | ❌ |
| Translation management | ✅ | ❌ |
| Queue job tracking | ✅ | ❌ |
| Filesystem I/O tracking | ✅ | ❌ |
| Event dispatcher tracking | ✅ | ❌ |
| Service call tracking | ✅ | ❌ |
| Embeddable toolbar | ✅ | ❌ |
| Multi-framework adapters (4) | ✅ | ❌ |
| OpenAPI ingestion spec | ✅ | ❌ |

---

## Prioritized Integration Roadmap

### Phase 1 — High Impact, Low Effort (1-2 weeks)

| # | Feature | Impact | Effort | Priority |
|---|---------|--------|--------|----------|
| 1 | HTTP Request Dump Server | Medium | Small | P1 |
| 2 | Ray Protocol Compatibility | Medium | Small | P1 |
| 3 | Monolog TCP Transport | Medium | Small | P1 |

**Rationale**: These extend ADP's reach to non-adapter users with minimal code changes. HTTP dump uses existing ingestion API. Ray maps to VarDumper. Monolog TCP extends existing CLI server.

### Phase 2 — High Impact, Medium Effort (2-4 weeks)

| # | Feature | Impact | Effort | Priority |
|---|---------|--------|--------|----------|
| 4 | Sentry Protocol Compatibility | High | Medium | P2 |
| 5 | SMTP Email Catcher | High | Medium | P2 |
| 6 | Webhooks (Outbound) | Medium | Medium | P2 |

**Rationale**: Sentry and SMTP are universally useful. Webhooks enable CI/CD integration.

### Phase 3 — High Impact, Large Effort (4-8 weeks)

| # | Feature | Impact | Effort | Priority |
|---|---------|--------|--------|----------|
| 7 | XHProf Profiler + Flame Charts | High | Large | P3 |

**Rationale**: Profiling with flame charts is the most impactful missing feature but requires significant frontend work (flame chart visualization).

### Phase 4 — Nice-to-Have (backlog)

| # | Feature | Impact | Effort | Priority |
|---|---------|--------|--------|----------|
| 8 | Inspector Protocol | Low-Med | Small | P4 |
| 9 | Project Tagging | Low-Med | Small | P4 |
| 10 | Authentication (JWT/OIDC) | Low-High | Medium | P4 |
| 11 | Metrics Endpoint | Low | Small | P4 |

---

## Implementation Details per Phase

### Phase 1 Implementation

#### 1.1 HTTP Request Dump Server

```
libs/API/src/Http/Handler/Ingest/HttpDumpHandler.php  (new)
```

- Add `POST /debug/api/ingest/http-dump` endpoint
- Capture: method, URI, headers, body, query, files
- Store as new collector type `http-dump` via existing ingestion pipeline
- Frontend: add `HttpDumpPage` to debug module with request details view

#### 1.2 Ray Protocol Compatibility

```
libs/API/src/Http/Handler/Ray/             (new directory)
  ├── EventHandler.php                     - Accept Ray payloads
  └── AvailabilityHandler.php              - Ray availability check
libs/API/src/Http/Middleware/RayDetector.php (new)
```

- Routes: `POST /_ray/api/...` (Ray's default endpoints)
- Merge multi-part payloads, strip VarDumper HTML tags
- Map to VarDumper collector format
- Support: colors, labels, screen clear, locks

#### 1.3 Monolog TCP in CLI Server

```
libs/Cli/src/Server/MonologTcpHandler.php   (new)
```

- Listen on configurable TCP port (default 9913, same as Buggregator)
- Parse newline-delimited JSON (Monolog\Handler\SocketHandler format)
- Feed into existing LogCollector or ingestion API

### Phase 2 Implementation

#### 2.1 Sentry Protocol

```
libs/API/src/Http/Handler/Sentry/           (new directory)
  ├── EnvelopeHandler.php                   - POST /api/{projectId}/envelope/
  ├── StoreHandler.php                      - POST /api/{projectId}/store/
  └── PayloadParser.php                     - Gzip + chunked JSON parsing
libs/Kernel/src/Event/SentryEvent.php        (new)
```

- Parse Sentry envelope format (header line + item headers + item payloads)
- Support gzip Content-Encoding
- Extract: exception, breadcrumbs, tags, user, request, contexts
- Map to ExceptionCollector-compatible format
- Frontend: Sentry-style error page with breadcrumb timeline

#### 2.2 SMTP Email Catcher

```
libs/Cli/src/Smtp/                           (new directory)
  ├── SmtpServer.php                        - TCP listener on port 1025
  ├── SmtpProtocol.php                      - SMTP state machine (EHLO/MAIL/RCPT/DATA)
  ├── MessageParser.php                     - MIME message parsing
  └── AttachmentExtractor.php               - Extract attachments
libs/Kernel/src/Collector/SmtpMessageCollector.php (new)
```

- Full SMTP protocol implementation (enough for dev use)
- Parse multipart MIME messages
- Store: sender, recipients, subject, HTML/text body, attachments
- Frontend: email list with HTML preview, attachment downloads, raw source view

#### 2.3 Webhooks

```
libs/Kernel/src/Webhook/                     (new directory)
  ├── WebhookConfig.php                     - Webhook definition
  ├── WebhookDispatcher.php                 - Event listener + HTTP sender
  ├── DeliveryTracker.php                   - Success/failure tracking
  └── RetryPolicy.php                       - Exponential backoff
libs/API/src/Http/Handler/Webhook/
  ├── ListHandler.php
  ├── CreateHandler.php
  └── DeliveryListHandler.php
```

- YAML + API configuration
- Fire on: new debug entry, exception, specific collector events
- Track delivery status, support retries
- Frontend: webhook CRUD page + delivery log

### Phase 3 Implementation

#### 3.1 XHProf Profiler

```
libs/Kernel/src/Profiler/                    (new directory)
  ├── ProfileBuilder.php                    - Parse XHProf data → call tree
  ├── Profile.php                           - Profile with peaks
  ├── Edge.php                              - Caller→callee with costs
  ├── Cost.php                              - {ct, wt, cpu, mu, pmu}
  ├── Peaks.php                             - Max values
  └── FlameChartBuilder.php                 - Build flame chart data
libs/API/src/Http/Handler/Profiler/
  ├── IngestHandler.php                     - POST /debug/api/ingest/profile
  ├── CallGraphHandler.php                  - GET /debug/api/profile/{id}/call-graph
  ├── FlameChartHandler.php                 - GET /debug/api/profile/{id}/flame-chart
  └── TopFunctionsHandler.php               - GET /debug/api/profile/{id}/top
libs/Cli/src/Command/ProfilerWatchCommand.php - Watch XHProf output directory
```

Frontend components (major effort):
- `FlameChart` — Interactive SVG/Canvas flame chart with zoom, search, hover details
- `CallGraph` — Sortable table with caller→callee, costs, percentages, diffs
- `TopFunctions` — Top N functions by CPU/wall-time/memory
- `ProfileSummary` — Overview with peaks

Reference implementation: Buggregator's `ProfileBuilder` at
`/tmp/buggregator-trap/src/Module/Profiler/XHProf/ProfileBuilder.php`

---

## Technical Notes

### Protocol Auto-Detection (from Buggregator Trap)

Buggregator Trap has an elegant traffic inspector that auto-detects protocols on a single port:
- Base64 + newline → VarDumper
- `{"message":` prefix → Monolog
- HTTP request line → HTTP (then middleware chain for Ray/Sentry/XHProf)
- Empty initial → SMTP handshake

ADP could adopt this for the CLI debug server to simplify configuration.

### Ingestion API Advantage

ADP already has an OpenAPI-spec'd ingestion API (`POST /debug/api/ingest/`).
Many Buggregator features can be implemented as:
1. Protocol adapter (translates Sentry/Ray/Inspector → ADP ingestion format)
2. New collector type in the ingestion schema
3. Frontend page for the new event type

This keeps the core clean and leverages existing infrastructure.

### Frontend Reuse

Buggregator's frontend (Vue 3 + Tailwind) cannot be directly reused in ADP (React + MUI).
However, the UI patterns and data structures can be referenced:
- Email preview layout → `src/entities/smtp/ui/`
- Profiler flame chart → `src/entities/profiler/ui/`
- Sentry error page → `src/entities/sentry/ui/`
- Ray dump view → `src/entities/ray/ui/`

---

## Summary

| Metric | Count |
|--------|-------|
| Features Buggregator has that ADP lacks | 11 |
| Features ADP has that Buggregator lacks | 20+ |
| High-priority features to add | 7 |
| Estimated total effort | 10-16 weeks |
| Phase 1 (quick wins) | 1-2 weeks |
| Phase 2 (core features) | 2-4 weeks |
| Phase 3 (profiler) | 4-8 weeks |

ADP is already a more feature-rich debugger for framework-integrated use cases. Adding Buggregator's protocol compatibility and standalone features would make ADP a **superset** of both tools.
