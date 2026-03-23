# Debug Panel Competitive Analysis — Feature Gap Report

## Methodology

Analyzed source code of 10+ debug panel solutions:
- **Symfony WebProfilerBundle** (cloned, full source analysis)
- **Laravel Telescope** (cloned, all watchers analyzed)
- **Laravel Debugbar** (cloned, all collectors analyzed)
- **Clockwork** (cloned, all data sources analyzed)
- **Spatie Ray** (cloned, all payloads analyzed)
- **Laravel Pulse** (cloned, all recorders analyzed)
- **Django Debug Toolbar** (cloned, all panels analyzed)
- **PHP Debug Bar** (cloned, all collectors analyzed)
- **Buggregator** (cloned, all modules analyzed)
- **Sentry JS SDK** (cloned, packages reviewed)
- **Blackfire.io**, **New Relic**, **Xdebug**, **OpenTelemetry** (web research)

## ADP Current Feature Inventory (Summary)

### What ADP Already Has
- 25+ core collectors (Log, DB, HTTP Client, Event, Exception, Cache, Mailer, Middleware, Router, Validator, View, Template, VarDumper, Queue, Service, Security, AssetBundle, Environment, Filesystem, HttpStream, Timeline, Request, AppInfo, Command, ConsoleAppInfo)
- 50+ API endpoints (Debug CRUD, SSE, Ingestion, Service Registry, Inspector)
- 22 debug panels (one per collector)
- 16+ inspector pages (Config, Routes, Database, Files, Translations, Cache, Git, Composer, Commands, Tests, Analysis, OPcache, PHPInfo)
- Toolbar with 6 metric items
- 5 CLI commands (serve, reset, broadcast, query, standalone HTTP server)
- PSR proxies (PSR-3, PSR-14, PSR-18, stream wrappers)
- 4 framework adapters (Yiisoft, Symfony, Laravel, Yii2)
- SSE real-time updates
- Multi-app support via service registry
- Code generation (Gii module)
- OpenAPI viewer
- Request re-execution + cURL builder
- Object serialization with circular reference detection
- Fuzzy search with keyboard layout transliteration

---

## Missing Features — Sorted by Complexity ↑ / Relevance ↓

### Tier 1: Low Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 1 | **Session data viewer** | Symfony, Django, Debugbar | Display session attributes, metadata, flash messages, session usage tracking with stack traces | Missing — Request panel exists but no dedicated session tab |
| 2 | **Memory usage panel** | Symfony, Django, PHP DebugBar | Dedicated memory panel: peak usage, PHP memory limit, warning thresholds, per-request tracking | Partial — Environment collector has peak memory, but no dedicated panel/warnings |
| 3 | **Not-Called Listeners panel** | Symfony | Show registered event listeners that were NOT triggered — helps find dead code | Missing — EventPanel only shows dispatched events |
| 4 | **Orphaned Events** | Symfony | Events dispatched with zero listeners — helps find misconfigured events | Missing |
| 5 | **Deprecation log filtering** | Symfony | Separate filtering for deprecation notices vs errors vs info in log panel | Missing — LogPanel has no deprecation-specific filtering |
| 6 | **Copy as cURL button** | Symfony, Clockwork | One-click "Copy as cURL" for any captured HTTP request (incoming or outgoing) | Partial — POST /curl/build exists but no one-click UI in panels |
| 7 | **Download email as EML** | Symfony | Download captured email messages as .eml files | Missing |
| 8 | **Email HTML preview iframe** | Symfony | Render HTML emails in a sandboxed iframe with text/HTML/source/MIME tabs | Missing — MailerPanel likely shows raw data |
| 9 | **Toolbar AJAX request tracking** | Symfony | Auto-intercept XHR/fetch calls and show them in the toolbar in real-time | Missing — toolbar shows static metrics only |
| 10 | **Redirect chain tracking** | Symfony, Django | Show redirect chain with source controller, status codes, and profiler links | Missing |
| 11 | **Color/label/separator for dumps** | Spatie Ray | Color-code, label, and visually separate dump output for organization | Missing — VarDumper shows raw dumps |
| 12 | **Gate/Authorization decisions** | Telescope, Debugbar | Track all authorization gate checks: ability, result, arguments, user | Partial — SecurityCollector has authorization but may lack detail |
| 13 | **Config collector** | Debugbar, Clockwork | Show application configuration values (useful for debugging env-specific issues) | Partial — Inspector has /config but no debug-time config snapshot |
| 14 | **Profiler search/filter** | Symfony | Search past profiles by token, IP, method, status code, URL, date range | Missing — ListPage exists but advanced search unclear |

### Tier 2: Low-Medium Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 15 | **Interactive execution timeline** | Symfony, Clockwork, Blackfire | SVG/Canvas timeline visualization with zoom, threshold filter, category colors, sub-request sections | Partial — TimelinePanel exists but likely basic table, not interactive SVG |
| 16 | **Query EXPLAIN in debug panel** | Django, Clockwork | Run EXPLAIN on any captured SQL query directly from the debug panel (not just inspector) | Partial — Inspector has EXPLAIN but not inline in DatabasePanel |
| 17 | **Duplicate query detection** | Django, Clockwork | Highlight N+1 queries and duplicate SQL statements automatically | Missing |
| 18 | **Slow query highlighting** | Telescope, Clockwork, Django | Visually mark queries exceeding a configurable threshold | Missing |
| 19 | **Redis command tracking** | Telescope, Clockwork | Monitor Redis GET/SET/DEL/etc. with timing and payload | Missing |
| 20 | **Notification tracking** | Telescope, Symfony | Track all notifications (email, SMS, Slack, push) with channel, recipient, response | Missing — MailerCollector covers email only |
| 21 | **Schedule/Cron monitoring** | Telescope, Pulse | Track scheduled task execution: name, expression, timing, output, status | Missing |
| 22 | **Model/Entity event tracking** | Telescope | Monitor ORM model events: created, updated, deleted, with changed attributes | Missing |
| 23 | **Form profiling panel** | Symfony | Interactive form tree: field hierarchy, validation errors per field, submitted vs default data, resolved options, view variables | Missing |
| 24 | **Signal handling panel** | Symfony | Show subscribed POSIX signals, handled signals with count/timing/memory | Missing |
| 25 | **Dark/Light/System theme toggle** | Symfony, Clockwork | Three-way theme with CSS variables and system preference detection | Partial — theme exists but verify three-way toggle |
| 26 | **Batch job tracking** | Telescope | Monitor batch job processing: total, pending, failed, completion callback | Missing |
| 27 | **Type-specific object casters** | Symfony VarDumper | 40+ specialized formatters for: PDO, Redis, Memcached, AMQP, cURL, DateTime, Intl, SPL, Doctrine, GD, UUID, FFI, PHPUnit/Mockery mocks | Missing — generic object serialization only |
| 28 | **IDE file link integration** | Symfony, Clockwork | Click-to-open-in-IDE links for file paths (configurable formatter: PHPStorm, VSCode, etc.) | Missing |
| 29 | **Parent/child profile linking** | Symfony | Link sub-requests and sub-commands to parent profiles for navigation | Missing |
| 30 | **Remote dump server (TCP)** | Symfony VarDumper | TCP-based dump collection from any process (workers, queues) to a central viewer | Partial — UDP server exists but limited protocol |

### Tier 3: Medium Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 31 | **Waterfall/flame chart** | Blackfire, Chrome DevTools, Clockwork | Hierarchical call graph visualization with timing per function | Missing — Timeline is flat |
| 32 | **Database connection pooling stats** | New Relic | Connection pool metrics: active, idle, wait time | Missing |
| 33 | **HTTP client cross-linking** | Symfony | Link outgoing HTTP requests to the remote app's debug profile (via x-debug-token header) | Missing |
| 34 | **Serializer profiling** | Symfony | Track serialize/deserialize operations: data type, normalizer/encoder used, timing, nested operations | Missing |
| 35 | **Workflow/State machine visualization** | Symfony | Render workflow definitions as interactive Mermaid.js diagrams with transition event listeners | Missing |
| 36 | **Persistent storage backends** | Clockwork, Telescope | SQL database storage (MySQL/PostgreSQL/SQLite), Redis storage for debug data | Partial — FileStorage + MemoryStorage only |
| 37 | **Webhook monitoring** | Buggregator | Capture and inspect incoming/outgoing webhooks | Missing |
| 38 | **SMTP server** | Buggregator | Built-in SMTP server to capture emails without sending them | Missing |
| 39 | **Metrics/counters dashboard** | Buggregator, Pulse | Aggregate metrics: requests/min, errors/min, avg response time over time | Missing |
| 40 | **User request attribution** | Pulse, Telescope | Track which user made which request, aggregate per-user statistics | Missing |
| 41 | **Data pruning/retention** | Telescope, Pulse | Configurable data retention with automatic pruning (by age, count, or type) | Missing |
| 42 | **CSP nonce handling** | Symfony | Generate and inject nonces for toolbar scripts/styles in CSP-protected pages | Missing |
| 43 | **Stateless route detection** | Symfony | Detect and warn when session is used in routes marked as stateless | Missing |

### Tier 4: Medium Complexity, Medium Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 44 | **Client-side Web Vitals** | Clockwork | Collect browser performance metrics (LCP, FID, CLS, TTFB) via JS package and display alongside server data | Missing |
| 45 | **Server-Timing W3C headers** | Clockwork, Debugbar | Send timeline events as W3C Server-Timing headers visible in browser DevTools Network tab | Missing |
| 46 | **Query re-execution** | Debugbar | Re-run captured SELECT queries and show results directly in the panel | Missing |
| 47 | **On-demand/sampling mode** | Clockwork, Pulse | Only collect when browser extension is open, or sample 1-in-N requests, or errors-only/slow-only modes | Missing |
| 48 | **Test profiling with assertions** | Clockwork | Collect PHPUnit test execution data with per-assertion pass/fail tracking | Missing |
| 49 | **Remote path mapping** | Debugbar, Clockwork, Ray | Map server file paths to local paths for Docker/Vagrant — makes IDE links work in containerized envs | Missing |
| 50 | **Gzip compressed storage** | Clockwork | Compress stored debug data files with gzip to reduce disk usage | Missing |
| 51 | **Pause/resume recording** | Telescope | CLI commands to pause/resume data collection without restarting the app | Missing |
| 52 | **Tag-based monitoring** | Telescope | Monitor specific tags (user ID, job class) to only record entries matching those tags | Missing |
| 53 | **Pre-aggregated time buckets** | Pulse | Aggregate data (count/min/max/sum/avg) at write time for efficient dashboard queries | Missing |
| 54 | **Request sampling per recorder** | Pulse | Different sample rates per collector (e.g., 100% for exceptions, 1% for cache) with deterministic sampling | Missing |
| 55 | **URL/key grouping via regex** | Pulse | Group similar URLs (`/users/*/orders`) and cache keys into categories for aggregation | Missing |
| 56 | **Livewire/Inertia component tracking** | Debugbar | Track SPA framework component renders, props, updates, data diffs | Missing (framework-specific) |
| 57 | **Feature flag tracking** | Debugbar (Pennant) | Monitor feature flag evaluations: flag name, result, scope | Missing |
| 58 | **Xdebug profiling integration** | Clockwork, Xdebug | Import Xdebug cachegrind/trace files for call graph analysis | Missing |
| 59 | **SQL query formatting** | Django, Clockwork | Pretty-print and syntax-highlight SQL with proper indentation | Missing — raw SQL likely |
| 60 | **Template rendering call graph** | Symfony | Visualize template include/extend hierarchy as a tree with timing | Missing — ViewPanel shows flat list |
| 61 | **Static file tracking** | Django | Monitor which static files (CSS, JS, images) are served and from which paths | Missing |
| 62 | **Signal/event subscribers list** | Django | Show all registered signal handlers, not just dispatched events | Missing — similar to not-called listeners |
| 63 | **Localization/translation collector** | PHP DebugBar | Track translation key lookups, missing translations, locale changes | Partial — Inspector has translations but no debug-time tracking |
| 64 | **Object instance counter** | PHP DebugBar | Count instances of specific classes created during request | Missing |
| 65 | **Sentry integration** | Buggregator | Accept Sentry SDK events and display them in the panel | Missing |
| 66 | **Ray protocol support** | Buggregator | Accept Spatie Ray payloads for display | Missing |
| 67 | **Monolog integration** | Buggregator | Accept Monolog log entries via TCP/UDP | Missing |
| 68 | **Execution pause/breakpoints** | Spatie Ray | Halt PHP execution from panel and resume from UI (like a remote breakpoint) | Missing |
| 69 | **PHPStan/Rector rules for cleanup** | Spatie Ray | Ship static analysis rules to detect and remove debug calls before production | Missing |
| 70 | **Deprecation warning handler** | Debugbar | Custom error handler for PHP deprecation notices with dedicated tracking | Missing |
| 71 | **Duplicate view grouping** | Debugbar | Auto-group repeated identical view renders with count (N+1 views detection) | Missing |
| 72 | **HTML response analysis (Alerts)** | Django | Auto-scan rendered HTML for common mistakes (missing enctype, broken forms, etc.) — static analysis on output | Missing |
| 73 | **Request history navigation** | Django | Navigate back to debug data of previous requests without re-triggering them | Partial — ListPage exists but unclear if it preserves full debug context inline |
| 74 | **cProfile call tree with HSV colors** | Django | Per-request cProfile profiling with color-coded call tree (depth + time proportion) distinguishing project vs library code | Missing |
| 75 | **xhprof flame charts** | Buggregator | xhprof-based profiling with flame charts, top functions, call graphs tracking CPU/wall/memory/peak memory per function | Missing |
| 76 | **WebSocket real-time (Centrifugo)** | Buggregator | Real-time updates via dedicated WebSocket server instead of SSE/polling — lower latency, bidirectional | Missing — SSE only |
| 77 | **Feature flag correlation with errors** | Sentry | When error occurs, capture exact feature flag states at that moment and attach to error event (5 provider integrations) | Missing |
| 78 | **GraphQL operation enrichment** | Sentry | Auto-parse GraphQL request bodies to enrich spans/breadcrumbs with operation names and types | Missing |
| 79 | **AI/LLM observability** | Sentry Spotlight | Dedicated inspection panel for AI application telemetry: LLM messages, tool calls, token usage | Missing |
| 80 | **Profile diff comparison** | Buggregator, Blackfire | Compare two profiling runs showing differences in CPU, wall time, and memory per function | Missing |

### Tier 5: High Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 81 | **Performance budgets/thresholds** | Pulse, Blackfire | Define acceptable limits (query count, response time, memory) and alert when exceeded | Missing |
| 82 | **Slow request aggregation** | Pulse | Aggregate slow requests over time periods, identify patterns and trends | Missing |
| 83 | **Server health monitoring** | Pulse | CPU, memory, disk, network metrics from application servers over time | Missing |
| 84 | **Distributed tracing** | OpenTelemetry, Sentry, New Relic | Trace requests across multiple services with span hierarchy and timing | Missing |
| 85 | **Session replay** | Sentry | Record user interactions (clicks, navigation, inputs) and replay them | Missing |
| 86 | **Error grouping & deduplication** | Sentry | Automatically group similar exceptions, track frequency and first/last seen | Missing |
| 87 | **User feedback widget** | Sentry | Allow end-users to submit feedback/bug reports with context | Missing |
| 88 | **HTTP dump server** | Buggregator | Accept arbitrary HTTP requests for inspection (webhook testing) | Missing |
| 89 | **Continuous profiling** | Blackfire | Always-on lightweight profiling in production with sampling | Missing |

### Tier 6: High Complexity, Medium Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 90 | **AI-powered analysis** | Blackfire, New Relic | Automatic performance recommendations based on collected data | Missing |
| 91 | **Call graph visualization** | Blackfire, Xdebug | Interactive function call graph with time/memory per node | Missing |
| 92 | **Comparison mode** | Blackfire | Compare two profiles side-by-side to find performance regressions | Missing |
| 93 | **Alerting system** | New Relic, Pulse, Sentry | Configure alerts for error rates, response times, custom metrics | Missing |
| 94 | **Database schema diff** | N/A (novel) | Compare database schema between requests or versions | Missing |
| 95 | **Custom dashboard builder** | Grafana, New Relic | User-configurable widgets/panels on a dashboard | Missing |
| 96 | **Plugin/extension system** | PHP DebugBar, Clockwork | Allow third-party packages to register custom panels and collectors | Partial — CollectorInterface exists but no frontend plugin API |
| 97 | **Export/share profiles** | Blackfire, Symfony | Export debug data as shareable files or links | Missing |
| 98 | **OpenTelemetry ingestion** | Buggregator, Sentry | Accept OTLP protocol data (traces, metrics, logs) | Missing |
| 99 | **SSO/Authentication** | Buggregator | Auth0, Kinde integration for multi-user access control | Missing |

### Tier 7: Very High Complexity, Lower Relevance (Aspirational)

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 100 | **Production-safe mode** | New Relic, Blackfire, Sentry | Lightweight agent for production with sampling and minimal overhead | Missing |
| 101 | **Mobile/responsive debug UI** | N/A (novel) | Debug panel optimized for mobile device debugging | Missing |
| 102 | **Collaborative debugging** | N/A (novel) | Share live debug sessions with team members | Missing |
| 103 | **Automated test generation** | N/A (novel) | Generate test cases from captured request/response pairs | Missing |
| 104 | **API version changelog** | N/A (novel) | Track API response changes between deployments | Missing |

### Tier 8: Features from APM/Observability Tools (Advanced, High-Impact)

These features come from professional APM tools (Blackfire, Datadog, Sentry, Grafana, OpenTelemetry) and represent the next level of debug panel evolution.

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 105 | **Performance assertions/budgets in config** | Blackfire | Define thresholds in config file (e.g., `queries < 5`, `memory < 10MB`), auto-evaluate per request and show pass/fail | Missing |
| 106 | **Profile comparison/diff** | Blackfire | Compare two request profiles side-by-side, highlight regressions in red, improvements in blue | Missing |
| 107 | **Breadcrumb trail** | Sentry | Automatic ordered log of ALL events (queries, logs, HTTP calls, clicks) leading to an error — unified cross-collector timeline | Missing — Timeline exists but not error-centric |
| 108 | **N+1 query auto-detection** | Sentry, Datadog | Detect repeated similar queries automatically (not just duplicates but pattern matching) | Missing |
| 109 | **Request phase breakdown** | Chrome DevTools | Split request into named phases (bootstrap, routing, controller, view rendering, response) with timing per phase | Missing — Timeline has events but no phase segmentation |
| 110 | **Timeline markers (user-defined)** | Blackfire | Allow developers to place custom annotations on the timeline via API call (`$debugger->mark('payment started')`) | Missing |
| 111 | **Trigger-based collector activation** | Xdebug | Enable expensive collectors per-request via header/cookie/query param — avoids always-on overhead | Missing |
| 112 | **Unified event timeline** | Vue DevTools | Single timeline combining events, state changes, queries, logs from ALL collectors chronologically | Partial — TimelineCollector exists but collectors may not all feed into it |
| 113 | **Service topology graph** | Grafana/Tempo | Auto-generate service dependency graph from collected HTTP/queue data showing error rates and latency per edge | Missing |
| 114 | **Multiple trace visualization modes** | Datadog | Same data viewable as waterfall, flame graph, span list, or service map — user switches between views | Missing — single view per panel |
| 115 | **Trace-as-flame-graph** | Grafana/Tempo | Render entire request lifecycle as an interactive flame graph (not profiling data but collector events) | Missing |
| 116 | **Issue grouping/deduplication** | Sentry | Group similar errors/exceptions by fingerprint, track frequency and first/last seen | Missing |
| 117 | **Logs-in-context** | New Relic | Auto-correlate log entries with specific request traces, view logs inline within request detail | Partial — LogCollector per-request but no cross-request correlation |
| 118 | **Code hotspots** | Datadog | Link trace spans to specific source code lines consuming the most CPU/memory | Missing |
| 119 | **Live search over recent data** | Datadog | Real-time search across recent requests by any attribute (15-min rolling window, no pre-indexing) | Missing |
| 120 | **Producer/Consumer span model** | OpenTelemetry | Model async jobs showing creation-to-processing lifecycle as linked spans | Missing — QueueCollector tracks but no span linking |
| 121 | **Baggage/context propagation** | OpenTelemetry | Pass debug context (tenant ID, feature flags) across service boundaries via headers | Missing |
| 122 | **Release-to-error mapping** | Sentry | Map every error to the specific release/commit/deploy, detect regressions per deploy | Missing |
| 123 | **AI-assisted analysis** | Sentry (Seer), Grafana, Chrome | LLM-powered interpretation of profiles/errors with automated fix suggestions | Missing |

---

## Quick-Win Recommendations (Top 15 by Impact/Effort Ratio)

1. **Session data viewer** — Add session tab to RequestPanel (Tier 1, ~2h)
2. **Not-Called Listeners + Orphaned Events** — Extend EventCollector to track registered-but-unused (Tier 1, ~4h)
3. **Toolbar AJAX tracking** — JS interceptor in toolbar for XHR/fetch (Tier 1, ~4h)
4. **Duplicate/N+1 query detection** — Analyze collected queries for duplicates and similar patterns (Tier 2, ~4h)
5. **Slow query highlighting** — Add threshold config to DatabasePanel (Tier 2, ~2h)
6. **Copy as cURL one-click** — Add button to RequestPanel and HttpClientPanel (Tier 1, ~2h)
7. **Email HTML preview** — Iframe rendering in MailerPanel with text/HTML/source tabs (Tier 1, ~3h)
8. **Redirect chain tracking** — Capture redirect info in RequestCollector (Tier 1, ~3h)
9. **Timeline markers API** — Allow `$debugger->mark('label')` to add custom annotations (Tier 8, ~3h)
10. **Performance assertions in config** — Define budgets (query count, memory, time) with pass/fail per request (Tier 8, ~6h)
11. **Request phase breakdown** — Segment timeline into named phases (bootstrap, routing, controller, response) (Tier 8, ~4h)
12. **Redis command tracking** — New collector for Redis operations (Tier 2, ~6h)
13. **Interactive SVG timeline** — Upgrade TimelinePanel to interactive SVG with zoom/filter (Tier 2, ~8h)
14. **IDE file link integration** — Configurable click-to-open-in-IDE links for all file paths (Tier 2, ~4h)
15. **Trigger-based collector activation** — Enable/disable collectors per-request via header (Tier 8, ~4h)

---

## Feature Count Comparison

| Tool | Total Unique Features | ADP Has | ADP Missing |
|------|----------------------:|--------:|------------:|
| Symfony WebProfiler | ~45 | ~25 | ~20 |
| Laravel Telescope | ~20 | ~12 | ~8 |
| Laravel Debugbar | ~18 | ~12 | ~6 |
| Clockwork | ~22 | ~14 | ~8 |
| Spatie Ray | ~15 | ~5 | ~10 |
| Laravel Pulse | ~10 | ~2 | ~8 |
| Django Debug Toolbar | ~12 | ~8 | ~4 |
| Buggregator | ~12 | ~4 | ~8 |
| Blackfire.io | ~15 | ~3 | ~12 |
| Sentry | ~12 | ~2 | ~10 |
| Datadog APM | ~15 | ~3 | ~12 |
| Grafana/Tempo | ~10 | ~1 | ~9 |
| OpenTelemetry | ~8 | ~2 | ~6 |
| Chrome DevTools | ~8 | ~1 | ~7 |
| Vue/React DevTools | ~10 | ~2 | ~8 |
| PHP DebugBar | ~10 | ~7 | ~3 |

**ADP's unique strengths not found in competitors:**
- Multi-framework support (4 adapters) — no other tool does this
- Inspector module (live app inspection) — unique to ADP
- Code generation (Gii module) — unique
- Composer package management from panel — unique
- Git operations from panel — unique
- Test/analysis runner from panel — unique
- Service registry for multi-app debugging — rare (only Buggregator has similar)
- Framework-agnostic ingestion API — rare
- Fuzzy search with keyboard layout transliteration — unique

---

## Strategic Roadmap Suggestion

### Phase 1: Parity with Symfony WebProfiler (Tier 1-2)
Focus: Session viewer, not-called listeners, AJAX tracking, duplicate queries, slow query highlighting, cURL copy, email preview, redirect tracking, IDE links. **~40h effort.**

### Phase 2: Enhanced Developer Experience (Tier 2-3)
Focus: Interactive SVG timeline, Redis tracking, notification tracking, form profiling, SQL query formatting, profile search, persistent storage backends. **~60h effort.**

### Phase 3: APM-Lite Features (Tier 8 selected)
Focus: Performance budgets, timeline markers, request phase breakdown, N+1 detection, trigger-based activation, breadcrumb trail. **~40h effort.**

### Phase 4: Advanced Visualization (Tier 5-6)
Focus: Flame graph rendering, profile comparison/diff, service topology graph, multiple trace views, issue grouping. **~80h effort.**

### Phase 5: Observability Integration (Tier 6-8)
Focus: OpenTelemetry ingestion, distributed tracing, context propagation, export/share profiles. **~100h effort.**
