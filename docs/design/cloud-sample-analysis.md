# Cloud Sample Analysis — Architecture & Feature Design

## Overview

Cloud Sample Analysis extends ADP with **asynchronous, background analysis** of debug samples.
Users send collected samples to an analysis backend (initially local processes, later a real cloud service)
that runs CPU-intensive or AI-powered analyzers and returns structured insights.

Results are stored alongside the original sample and surfaced in the ADP frontend as an "Analysis" tab
with per-analyzer cards, severity badges, and actionable recommendations.

## Goals

1. **Decouple analysis from collection** — collectors stay fast; analysis runs asynchronously
2. **Pluggable analyzers** — easy to add new analysis types without changing core
3. **Local-first** — runs as local child processes initially; same interface works with a remote cloud later
4. **Non-blocking** — analysis never slows down the target application or the ADP panel
5. **Incremental** — results stream in as each analyzer finishes; no need to wait for all

---

## What Can Be Analyzed

### 1. Performance Analysis

| Analyzer | Input Collectors | Output |
|----------|-----------------|--------|
| **SlowQueryDetector** | `DatabaseCollector` | Queries exceeding threshold, estimated complexity (full scan, missing index hints) |
| **N+1 QueryDetector** | `DatabaseCollector` | Groups of similar queries executed in a loop pattern, with count and suggested eager-load |
| **DuplicateQueryAnalyzer** | `DatabaseCollector` | Already-detected duplicates enriched with caching recommendations |
| **SlowRouteDetector** | `WebAppInfoCollector`, `RouterCollector` | Routes with processing time above P95, bottleneck breakdown |
| **MiddlewareBottleneck** | `MiddlewareCollector` | Middleware with disproportionate time/memory consumption |
| **MemoryLeakHint** | `WebAppInfoCollector` | Peak memory vs baseline comparison, growth pattern detection |
| **HttpClientLatency** | `HttpClientCollector` | External API calls ranked by latency, timeout risk scoring |

### 2. Security Analysis

| Analyzer | Input Collectors | Output |
|----------|-----------------|--------|
| **SqlInjectionRisk** | `DatabaseCollector` | Raw SQL containing unparameterized user input patterns |
| **SensitiveDataExposure** | `LogCollector`, `VarDumperCollector` | Passwords, tokens, keys leaked in logs or dumps |
| **InsecureHeaderCheck** | `RequestCollector` | Missing security headers (CSP, HSTS, X-Frame-Options) |
| **ExceptionInfoLeak** | `ExceptionCollector` | Stack traces exposing internal paths in non-debug mode |

### 3. Quality Analysis

| Analyzer | Input Collectors | Output |
|----------|-----------------|--------|
| **UnusedServiceDetector** | `ServiceCollector`, `EventCollector` | Registered but never-called services |
| **DeprecationImpact** | `DeprecationCollector` | Grouped deprecations by severity and upgrade path |
| **EventListenerAudit** | `EventCollector` | Events with no listeners, listeners that throw |
| **CacheEfficiency** | `CacheCollector` | Hit ratio analysis, cold-cache patterns, key entropy |
| **ViewRenderCost** | `ViewCollector`, `TemplateCollector` | Render time distribution, duplicate render elimination opportunities |

### 4. Behavioral Analysis

| Analyzer | Input Collectors | Output |
|----------|-----------------|--------|
| **RequestAnomalyDetector** | All summary data | Statistical outlier detection vs historical baseline |
| **ErrorCorrelation** | `ExceptionCollector`, `LogCollector`, `DatabaseCollector` | Causal chains (e.g., DB timeout -> exception -> error log) |
| **TimelineGapAnalyzer** | `TimelineCollector` | Unexplained gaps in the request timeline |
| **QueueReliability** | `QueueCollector` | Failed message patterns, retry storms, duplicate dispatch |

### 5. AI-Powered Analysis (Future)

| Analyzer | Input | Output |
|----------|-------|--------|
| **QueryOptimizer** | SQL queries + schema | Rewritten queries with EXPLAIN-based suggestions |
| **ExceptionRootCause** | Exception + surrounding context | Natural language root cause explanation |
| **PerformanceSummary** | Full sample | Human-readable performance report with priorities |
| **CodeSmellDetector** | Stack traces + collector data | Anti-pattern detection with fix suggestions |

---

## Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────────────┐
│                     ADP Frontend                        │
│  ┌──────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │Debug List │  │ Detail View  │  │  Analysis Tab    │  │
│  │ (badges)  │  │  (existing)  │  │ (new: insights)  │  │
│  └──────────┘  └──────────────┘  └──────────────────┘  │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP
┌──────────────────────▼──────────────────────────────────┐
│                     ADP API                              │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Analysis Controller (new)                         │   │
│  │  GET  /debug/api/analysis/{id}                    │   │
│  │  POST /debug/api/analysis/{id}/run                │   │
│  │  GET  /debug/api/analysis/{id}/stream (SSE)       │   │
│  │  GET  /debug/api/analysis/config                  │   │
│  │  PUT  /debug/api/analysis/config                  │   │
│  └──────────────────────┬───────────────────────────┘   │
│                         │                                │
│  ┌──────────────────────▼───────────────────────────┐   │
│  │ AnalysisManager                                   │   │
│  │  - Orchestrates analyzers                         │   │
│  │  - Manages job lifecycle                          │   │
│  │  - Stores results                                 │   │
│  └──────────────────────┬───────────────────────────┘   │
└─────────────────────────┼────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
┌─────────▼──┐  ┌────────▼───┐  ┌────────▼───┐
│   Local     │  │   Local     │  │   Cloud     │
│  Process    │  │  Process    │  │  Backend    │
│ (Analyzer1) │  │ (Analyzer2) │  │  (future)   │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Key Abstractions

```
libs/Kernel/src/Analysis/
├── AnalyzerInterface.php          # Contract for all analyzers
├── AnalysisResult.php             # Value object: findings, severity, metadata
├── AnalysisFinding.php            # Single finding: type, message, severity, data, suggestions
├── AnalysisJobStatus.php          # Enum: pending, running, completed, failed
├── AnalysisJob.php                # Tracks one analyzer execution against one sample
├── AnalysisManagerInterface.php   # Orchestration contract
├── AnalysisManager.php            # Default implementation
├── AnalysisStorageInterface.php   # Persistence for analysis results
├── FileAnalysisStorage.php        # JSON file storage (alongside sample data)
├── Runner/
│   ├── AnalysisRunnerInterface.php    # How to execute an analyzer
│   ├── LocalProcessRunner.php         # Runs analyzer as a local PHP child process
│   └── HttpRunner.php                 # Sends sample to remote HTTP endpoint (cloud)
└── Analyzer/
    ├── Performance/
    │   ├── SlowQueryAnalyzer.php
    │   ├── NPlusOneAnalyzer.php
    │   ├── DuplicateQueryAnalyzer.php
    │   ├── SlowRouteAnalyzer.php
    │   └── HttpClientLatencyAnalyzer.php
    ├── Security/
    │   ├── SqlInjectionRiskAnalyzer.php
    │   ├── SensitiveDataAnalyzer.php
    │   └── InsecureHeaderAnalyzer.php
    ├── Quality/
    │   ├── CacheEfficiencyAnalyzer.php
    │   ├── DeprecationImpactAnalyzer.php
    │   └── EventListenerAuditAnalyzer.php
    └── Behavioral/
        ├── RequestAnomalyAnalyzer.php
        ├── ErrorCorrelationAnalyzer.php
        └── TimelineGapAnalyzer.php
```

### Core Interfaces

```php
interface AnalyzerInterface
{
    /** Unique analyzer identifier (e.g., "performance.slow-query") */
    public function getId(): string;

    /** Human-readable name */
    public function getName(): string;

    /** Category: performance, security, quality, behavioral */
    public function getCategory(): string;

    /** Which collector class IDs this analyzer requires */
    public function getRequiredCollectors(): array;

    /** Run analysis on the given sample data, return findings */
    public function analyze(AnalysisContext $context): AnalysisResult;
}

interface AnalysisRunnerInterface
{
    /** Execute an analyzer asynchronously, return a job handle */
    public function run(AnalyzerInterface $analyzer, string $sampleId): AnalysisJob;

    /** Check if the runner supports a given analyzer */
    public function supports(AnalyzerInterface $analyzer): bool;
}

interface AnalysisManagerInterface
{
    /** Run all applicable analyzers for a sample */
    public function analyzeSample(string $sampleId, ?array $analyzerIds = null): array;

    /** Get analysis results for a sample */
    public function getResults(string $sampleId): array;

    /** Get job statuses for a sample */
    public function getJobs(string $sampleId): array;

    /** Get available analyzers */
    public function getAnalyzers(): array;
}

interface AnalysisStorageInterface
{
    public function saveResult(string $sampleId, string $analyzerId, AnalysisResult $result): void;
    public function getResults(string $sampleId): array;
    public function getResult(string $sampleId, string $analyzerId): ?AnalysisResult;
    public function hasResults(string $sampleId): bool;
}
```

### Value Objects

```php
final class AnalysisResult
{
    public function __construct(
        public readonly string $analyzerId,
        public readonly string $analyzerName,
        public readonly string $category,
        public readonly AnalysisStatus $status,          // ok | warning | critical | error
        /** @var list<AnalysisFinding> */
        public readonly array $findings,
        public readonly float $duration,                  // seconds
        public readonly array $metadata = [],
    ) {}
}

final class AnalysisFinding
{
    public function __construct(
        public readonly string $type,                     // e.g., "slow-query", "n-plus-one"
        public readonly Severity $severity,               // info | warning | critical
        public readonly string $message,                  // Human-readable description
        public readonly ?string $collector = null,        // Source collector class
        public readonly array $data = [],                 // Structured evidence
        public readonly array $suggestions = [],          // Actionable fix suggestions
    ) {}
}

enum AnalysisStatus: string
{
    case Ok = 'ok';                 // No issues found
    case Warning = 'warning';       // Minor issues
    case Critical = 'critical';     // Serious issues
    case Error = 'error';           // Analyzer failed
}

enum Severity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
}
```

---

## Storage Layout

Analysis results are stored alongside the sample in the existing directory structure:

```
storage/YYYY-MM-DD/{id}/
├── summary.json.gz          # Existing
├── data.json.gz             # Existing
├── objects.json.gz          # Existing
└── analysis.json.gz         # NEW: analysis results
```

`analysis.json.gz` structure:

```json
{
  "analyzedAt": "2026-03-27T14:30:00+00:00",
  "duration": 2.45,
  "status": "warning",
  "results": {
    "performance.slow-query": {
      "analyzerId": "performance.slow-query",
      "analyzerName": "Slow Query Detector",
      "category": "performance",
      "status": "warning",
      "findings": [
        {
          "type": "slow-query",
          "severity": "warning",
          "message": "Query took 850ms (threshold: 100ms)",
          "collector": "AppDevPanel\\Kernel\\Collector\\DatabaseCollector",
          "data": {
            "sql": "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
            "duration": 0.85,
            "threshold": 0.1,
            "position": 3
          },
          "suggestions": [
            "Add index on orders(user_id, created_at DESC)",
            "Consider pagination with LIMIT/OFFSET"
          ]
        }
      ],
      "duration": 0.012,
      "metadata": {"queryCount": 15, "analyzedCount": 15}
    },
    "performance.n-plus-one": {
      "analyzerId": "performance.n-plus-one",
      "status": "critical",
      "findings": [
        {
          "type": "n-plus-one",
          "severity": "critical",
          "message": "N+1 pattern detected: 25 similar SELECT on 'products' table",
          "data": {
            "pattern": "SELECT * FROM products WHERE id = ?",
            "count": 25,
            "totalDuration": 0.45
          },
          "suggestions": [
            "Use eager loading: SELECT * FROM products WHERE id IN (?...)",
            "Consider batch fetching with a single query"
          ]
        }
      ]
    }
  }
}
```

---

## API Endpoints

### Analysis Controller

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug/api/analysis/{id}` | Get all analysis results for a sample |
| POST | `/debug/api/analysis/{id}/run` | Trigger analysis (optional body: `{analyzers?: string[]}`) |
| GET | `/debug/api/analysis/{id}/stream` | SSE stream for real-time analysis progress |
| GET | `/debug/api/analysis/analyzers` | List available analyzers with categories |
| GET | `/debug/api/analysis/config` | Get analysis configuration (thresholds, enabled analyzers) |
| PUT | `/debug/api/analysis/config` | Update analysis configuration |

### Response Examples

**GET `/debug/api/analysis/{id}`**

```json
{
  "id": "5f4a9e8c3b2a1",
  "data": {
    "analyzedAt": "2026-03-27T14:30:00+00:00",
    "status": "warning",
    "duration": 2.45,
    "summary": {
      "total": 8,
      "ok": 5,
      "warning": 2,
      "critical": 1,
      "error": 0
    },
    "results": { ... }
  },
  "success": true,
  "status": 200
}
```

**POST `/debug/api/analysis/{id}/run`**

```json
{
  "id": "5f4a9e8c3b2a1",
  "data": {
    "jobs": [
      {"analyzer": "performance.slow-query", "status": "running"},
      {"analyzer": "performance.n-plus-one", "status": "pending"},
      {"analyzer": "security.sensitive-data", "status": "pending"}
    ]
  },
  "success": true,
  "status": 202
}
```

**SSE `/debug/api/analysis/{id}/stream`**

```
event: analysis.started
data: {"analyzer": "performance.slow-query", "status": "running"}

event: analysis.completed
data: {"analyzer": "performance.slow-query", "status": "warning", "findingCount": 2}

event: analysis.completed
data: {"analyzer": "performance.n-plus-one", "status": "critical", "findingCount": 1}

event: analysis.done
data: {"status": "warning", "total": 8, "duration": 2.45}
```

---

## Local Process Runner

The initial implementation uses local PHP child processes via `proc_open`:

```php
final class LocalProcessRunner implements AnalysisRunnerInterface
{
    /**
     * Spawns: php bin/adp-analyze <analyzer-id> <sample-id> <storage-path>
     *
     * The child process:
     * 1. Reads sample data from storage
     * 2. Instantiates the analyzer
     * 3. Runs analyze()
     * 4. Writes AnalysisResult as JSON to stdout
     * 5. Parent reads stdout and stores result
     */
    public function run(AnalyzerInterface $analyzer, string $sampleId): AnalysisJob;
}
```

The CLI entrypoint (`bin/adp-analyze`) is a minimal script:

```php
#!/usr/bin/env php
<?php
// bin/adp-analyze
// Usage: php bin/adp-analyze <analyzer-id> <sample-id> <storage-path>

$analyzerId = $argv[1];
$sampleId = $argv[2];
$storagePath = $argv[3];

$storage = new FileStorage($storagePath);
$context = AnalysisContext::fromStorage($storage, $sampleId);
$analyzer = AnalyzerRegistry::create($analyzerId);

$result = $analyzer->analyze($context);
echo json_encode($result, JSON_THROW_ON_ERROR);
```

### Parallelism

`AnalysisManager` runs analyzers in parallel using non-blocking `proc_open`:

```php
// Simplified flow
$processes = [];
foreach ($analyzers as $analyzer) {
    $processes[] = $this->runner->run($analyzer, $sampleId);
}

// Poll until all complete (with timeout)
while ($running = array_filter($processes, fn($j) => $j->isRunning())) {
    usleep(50_000); // 50ms polling interval
}
```

---

## Frontend Integration

### Summary Badge in Debug List

The debug entry list shows a small analysis badge when results exist:

```
[GET /api/users] 200 45ms  ⚠ 2 warnings  ✕ 1 critical
```

The summary data is extended with an `analysis` field:

```json
{
  "id": "...",
  "request": {...},
  "response": {...},
  "analysis": {
    "status": "warning",
    "counts": {"ok": 5, "warning": 2, "critical": 1}
  }
}
```

### Analysis Tab in Detail View

New tab alongside existing collector tabs (Logs, DB, Events, etc.):

```
[Request] [Response] [Logs] [DB] [Events] [Analysis ⚠]
```

Content structure:

```
┌─ Analysis Results ──────────────────────────────────────┐
│                                                          │
│  Overall: ⚠ Warning  |  8 analyzers  |  2.4s            │
│                                                          │
│  ┌─ Performance ─────────────────────────────────────┐   │
│  │ ✕ N+1 Query Pattern (critical)                    │   │
│  │   25× SELECT * FROM products WHERE id = ?         │   │
│  │   Total: 450ms                                    │   │
│  │   → Use eager loading with IN clause              │   │
│  │                                                   │   │
│  │ ⚠ Slow Query (warning)                            │   │
│  │   SELECT * FROM orders WHERE user_id = ? ...      │   │
│  │   Duration: 850ms (threshold: 100ms)              │   │
│  │   → Add index on orders(user_id, created_at)      │   │
│  └───────────────────────────────────────────────────┘   │
│                                                          │
│  ┌─ Security ────────────────────────────────────────┐   │
│  │ ✓ All checks passed                               │   │
│  └───────────────────────────────────────────────────┘   │
│                                                          │
│  ┌─ Quality ─────────────────────────────────────────┐   │
│  │ ⚠ Cache hit ratio: 23% (threshold: 60%)           │   │
│  │   → Review cache key strategy for /api/users      │   │
│  └───────────────────────────────────────────────────┘   │
│                                                          │
│  [Re-run Analysis]  [Configure Thresholds]               │
└──────────────────────────────────────────────────────────┘
```

### Auto-Analysis

Configurable via settings:

| Setting | Default | Description |
|---------|---------|-------------|
| `analysis.autoRun` | `false` | Auto-analyze every new sample |
| `analysis.autoRunAnalyzers` | `["performance.*"]` | Which analyzers to auto-run |
| `analysis.thresholds.slowQuery` | `100` | Slow query threshold (ms) |
| `analysis.thresholds.slowRoute` | `500` | Slow route threshold (ms) |
| `analysis.thresholds.cacheHitRatio` | `0.6` | Minimum acceptable cache hit ratio |
| `analysis.thresholds.nPlusOneMinCount` | `5` | Minimum similar queries to flag N+1 |

---

## Cloud Backend (Future)

The `HttpRunner` sends samples to a remote analysis service:

```
POST https://cloud.appdevpanel.com/api/v1/analyze
Content-Type: application/json
Authorization: Bearer <api-key>

{
  "sampleId": "5f4a9e8c3b2a1",
  "analyzers": ["performance.*", "security.*"],
  "data": { <summary + data from storage> },
  "config": { <user thresholds> }
}
```

The cloud backend:
1. Receives sample data
2. Runs heavier analyzers (AI-powered, cross-sample correlation)
3. Returns results via webhook or polling
4. Optional: retains anonymized samples for training/benchmarking

The `AnalysisRunnerInterface` abstraction means switching from `LocalProcessRunner` to `HttpRunner`
requires zero changes in `AnalysisManager`, the API, or the frontend.

---

## Integration with User's ADP

### How It Connects

1. **Storage-level**: Analysis results live in the same directory as the sample (`analysis.json.gz`)
2. **API-level**: New `/debug/api/analysis/*` endpoints follow existing conventions (same middleware, same response wrapper)
3. **Frontend-level**: New "Analysis" tab reuses existing SDK components (cards, badges, code blocks)
4. **SSE-level**: Analysis completion events use the existing `event-stream` infrastructure
5. **MCP-level**: New MCP tools expose analysis results to AI assistants

### MCP Integration

New MCP tools for AI assistant access:

| Tool | Description |
|------|-------------|
| `analyze_sample` | Run analysis on a debug sample |
| `get_analysis` | Get analysis results for a sample |
| `list_analyzers` | List available analyzers |
| `get_findings` | Get findings filtered by severity/category |

### Multi-App Support

Analysis works with the existing service registry:
- Each registered service's samples can be analyzed independently
- Cross-service analysis (e.g., "Service A calls Service B, and B is slow") is a future cloud feature
- The `?service=` query parameter convention extends to analysis endpoints

---

## Implementation Phases

### Phase 1: Foundation
- `AnalyzerInterface`, `AnalysisResult`, `AnalysisFinding` value objects
- `FileAnalysisStorage` (read/write `analysis.json.gz`)
- `AnalysisManager` with synchronous in-process execution
- `AnalysisController` with GET/POST endpoints
- 3 initial analyzers: `SlowQueryAnalyzer`, `NPlusOneAnalyzer`, `CacheEfficiencyAnalyzer`

### Phase 2: Async Execution
- `LocalProcessRunner` with `proc_open`
- `bin/adp-analyze` CLI entrypoint
- SSE streaming for real-time progress
- Parallel analyzer execution

### Phase 3: Frontend
- Analysis tab in detail view
- Summary badges in debug list
- Configuration UI for thresholds
- "Run Analysis" button

### Phase 4: Extended Analyzers
- Full performance suite (slow routes, middleware bottlenecks, HTTP latency)
- Security suite (SQL injection risk, sensitive data, headers)
- Quality suite (deprecations, events, views)

### Phase 5: Cloud & AI
- `HttpRunner` for remote analysis
- AI-powered analyzers (query optimization, root cause, summaries)
- Cross-sample trend analysis
- Anomaly detection with historical baselines
