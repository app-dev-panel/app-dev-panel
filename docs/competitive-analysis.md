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

### Tier 3: Medium Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 27 | **Waterfall/flame chart** | Blackfire, Chrome DevTools, Clockwork | Hierarchical call graph visualization with timing per function | Missing — Timeline is flat |
| 28 | **Database connection pooling stats** | New Relic | Connection pool metrics: active, idle, wait time | Missing |
| 29 | **HTTP client cross-linking** | Symfony | Link outgoing HTTP requests to the remote app's debug profile (via x-debug-token header) | Missing |
| 30 | **Serializer profiling** | Symfony | Track serialize/deserialize operations: data type, normalizer/encoder used, timing, nested operations | Missing |
| 31 | **Workflow/State machine visualization** | Symfony | Render workflow definitions as interactive Mermaid.js diagrams with transition event listeners | Missing |
| 32 | **Persistent storage backends** | Clockwork, Telescope | SQL database storage (MySQL/PostgreSQL/SQLite), Redis storage for debug data | Partial — FileStorage + MemoryStorage only |
| 33 | **Webhook monitoring** | Buggregator | Capture and inspect incoming/outgoing webhooks | Missing |
| 34 | **SMTP server** | Buggregator | Built-in SMTP server to capture emails without sending them | Missing |
| 35 | **Metrics/counters dashboard** | Buggregator, Pulse | Aggregate metrics: requests/min, errors/min, avg response time over time | Missing |
| 36 | **User request attribution** | Pulse, Telescope | Track which user made which request, aggregate per-user statistics | Missing |
| 37 | **Data pruning/retention** | Telescope, Pulse | Configurable data retention with automatic pruning (by age, count, or type) | Missing |

### Tier 4: Medium Complexity, Medium Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 38 | **Livewire/Inertia component tracking** | Debugbar | Track SPA framework component renders, props, updates | Missing (framework-specific) |
| 39 | **Feature flag tracking** | Debugbar (Pennant) | Monitor feature flag evaluations: flag name, result, scope | Missing |
| 40 | **Xdebug profiling integration** | Clockwork, Xdebug | Import Xdebug cachegrind/trace files for call graph analysis | Missing |
| 41 | **SQL query formatting** | Django, Clockwork | Pretty-print and syntax-highlight SQL with proper indentation | Missing — raw SQL likely |
| 42 | **Template rendering call graph** | Symfony | Visualize template include/extend hierarchy as a tree with timing | Missing — ViewPanel shows flat list |
| 43 | **Static file tracking** | Django | Monitor which static files (CSS, JS, images) are served and from which paths | Missing |
| 44 | **Signal/event subscribers list** | Django | Show all registered signal handlers, not just dispatched events | Missing — similar to not-called listeners |
| 45 | **Localization/translation collector** | PHP DebugBar | Track translation key lookups, missing translations, locale changes | Partial — Inspector has translations but no debug-time tracking |
| 46 | **Object instance counter** | PHP DebugBar | Count instances of specific classes created during request | Missing |
| 47 | **Sentry integration** | Buggregator | Accept Sentry SDK events and display them in the panel | Missing |
| 48 | **Ray protocol support** | Buggregator | Accept Spatie Ray payloads for display | Missing |
| 49 | **Monolog integration** | Buggregator | Accept Monolog log entries via TCP/UDP | Missing |

### Tier 5: High Complexity, High Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 50 | **Performance budgets/thresholds** | Pulse, Blackfire | Define acceptable limits (query count, response time, memory) and alert when exceeded | Missing |
| 51 | **Slow request aggregation** | Pulse | Aggregate slow requests over time periods, identify patterns and trends | Missing |
| 52 | **Server health monitoring** | Pulse | CPU, memory, disk, network metrics from application servers over time | Missing |
| 53 | **Distributed tracing** | OpenTelemetry, Sentry, New Relic | Trace requests across multiple services with span hierarchy and timing | Missing |
| 54 | **Session replay** | Sentry | Record user interactions (clicks, navigation, inputs) and replay them | Missing |
| 55 | **Error grouping & deduplication** | Sentry | Automatically group similar exceptions, track frequency and first/last seen | Missing |
| 56 | **User feedback widget** | Sentry | Allow end-users to submit feedback/bug reports with context | Missing |
| 57 | **HTTP dump server** | Buggregator | Accept arbitrary HTTP requests for inspection (webhook testing) | Missing |
| 58 | **Continuous profiling** | Blackfire | Always-on lightweight profiling in production with sampling | Missing |

### Tier 6: High Complexity, Medium Relevance

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 59 | **AI-powered analysis** | Blackfire, New Relic | Automatic performance recommendations based on collected data | Missing |
| 60 | **Call graph visualization** | Blackfire, Xdebug | Interactive function call graph with time/memory per node | Missing |
| 61 | **Comparison mode** | Blackfire | Compare two profiles side-by-side to find performance regressions | Missing |
| 62 | **Alerting system** | New Relic, Pulse, Sentry | Configure alerts for error rates, response times, custom metrics | Missing |
| 63 | **Database schema diff** | N/A (novel) | Compare database schema between requests or versions | Missing |
| 64 | **Custom dashboard builder** | Grafana, New Relic | User-configurable widgets/panels on a dashboard | Missing |
| 65 | **Plugin/extension system** | PHP DebugBar, Clockwork | Allow third-party packages to register custom panels and collectors | Partial — CollectorInterface exists but no frontend plugin API |
| 66 | **Export/share profiles** | Blackfire, Symfony | Export debug data as shareable files or links | Missing |
| 67 | **OpenTelemetry ingestion** | Buggregator, Sentry | Accept OTLP protocol data (traces, metrics, logs) | Missing |
| 68 | **SSO/Authentication** | Buggregator | Auth0, Kinde integration for multi-user access control | Missing |

### Tier 7: Very High Complexity, Lower Relevance (Aspirational)

| # | Feature | Source | Description | ADP Status |
|---|---------|--------|-------------|------------|
| 69 | **Production-safe mode** | New Relic, Blackfire, Sentry | Lightweight agent for production with sampling and minimal overhead | Missing |
| 70 | **Mobile/responsive debug UI** | N/A (novel) | Debug panel optimized for mobile device debugging | Missing |
| 71 | **Collaborative debugging** | N/A (novel) | Share live debug sessions with team members | Missing |
| 72 | **Automated test generation** | N/A (novel) | Generate test cases from captured request/response pairs | Missing |
| 73 | **API version changelog** | N/A (novel) | Track API response changes between deployments | Missing |

---

## Quick-Win Recommendations (Top 10 by Impact/Effort Ratio)

1. **Session data viewer** — Add session tab to RequestPanel
2. **Not-Called Listeners + Orphaned Events** — Extend EventCollector to track registered-but-unused
3. **Toolbar AJAX tracking** — JS interceptor in toolbar for XHR/fetch
4. **Duplicate query detection** — Analyze collected queries for duplicates/N+1
5. **Slow query highlighting** — Add threshold config to DatabasePanel
6. **Copy as cURL one-click** — Add button to RequestPanel and HttpClientPanel
7. **Email HTML preview** — Iframe rendering in MailerPanel
8. **Redirect chain tracking** — Capture redirect info in RequestCollector
9. **Interactive timeline** — Upgrade TimelinePanel from table to SVG visualization
10. **Redis command tracking** — New collector for Redis operations

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
