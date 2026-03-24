# 2. Product Overview

## 2.1 What It Is

ADP is a debugging and development panel that:
- Collects runtime data from the application (logs, events, requests, exceptions, SQL, cache, queues, mail)
- Provides a web UI for inspection and analysis
- Works as an SPA + embeddable toolbar
- Supports real-time streaming via UDP and SSE

## 2.2 Architecture (Marketing Angle)

```
Any PHP application → Adapter → Kernel (28 collectors) → API (40+ endpoints) → React SPA
```

**What this means for a developer:**
- Install the package — it works
- One UI for all projects (Symfony in the morning, Laravel in the evening — same ADP)
- No need to learn different tools for different frameworks

## 2.3 Full Feature Catalog

**28 data collectors:**

| Category | Collectors | What the Developer Sees |
|----------|-----------|------------------------|
| Logging | LogCollector, VarDumperCollector | All logs with levels, dump() calls |
| HTTP | RequestCollector, HttpClientCollector, HttpStreamCollector | Incoming/outgoing requests, headers, bodies |
| Database | DatabaseCollector | SQL queries, execution time, bindings, backtrace |
| Errors | ExceptionCollector | Stack traces, chained exceptions, context |
| Events | EventCollector | All dispatched events with timing |
| Performance | TimelineCollector, WebAppInfoCollector, ConsoleAppInfoCollector | Timeline, memory, request time |
| Middleware | MiddlewareCollector | Middleware stack with before/handler/after phases |
| Cache | CacheCollector | Hit/miss, operations, keys |
| Mail | MailerCollector | Sent emails with preview |
| Queues | QueueCollector | Jobs, statuses, duration |
| Validation | ValidatorCollector | Rules and validation errors |
| Routing | RouterCollector | Matched routes, controllers |
| Templates | TwigCollector, WebViewCollector, AssetBundleCollector | Rendered templates, assets |
| Security | SecurityCollector | User, roles, firewall |
| Environment | EnvironmentCollector | PHP config, variables |
| Filesystem | FilesystemStreamCollector | File operations |
| Services | ServiceCollector | DI container, method calls |

**Inspector — live application introspection (20+ pages):**

| Page | What It Does |
|------|-------------|
| Configuration | DI container parameters |
| Routes | Route browser with validation |
| Database | DB schema, table browsing with pagination |
| File Explorer | Project file navigation |
| Git | Status, log, checkout directly from the panel |
| Commands | Run CLI commands from the UI |
| Composer | Package management |
| Cache | View and clear cache |
| OPcache | OPcache statistics |
| Translations | View and edit translations |
| Tests | Run tests |
| Events | All event listeners |
| Container | DI container services |
| PHPInfo | PHP configuration |

**Developer tools:**
- **GenCode** — code generation with preview and diff
- **OpenAPI** — Swagger UI for the API
- **cURL Builder** — generate cURL commands from requests
- **Request Replay** — re-execute requests

---

## Actions

- [ ] Create a visual architecture diagram for marketing materials
- [ ] Prepare the collector table in a format suitable for landing page/README
- [ ] Record a screencast demonstrating each collector category
- [ ] Prepare GIF animations for key Inspector features
