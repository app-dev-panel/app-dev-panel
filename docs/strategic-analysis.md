# ADP Strategic Analysis

Status: 2026-03-30.

## Competitive Position

| Feature | ADP | Symfony Profiler | Laravel Telescope | Clockwork |
|---------|:---:|:----------------:|:-----------------:|:---------:|
| Framework-agnostic | yes | no | no | partial |
| Live app inspection | yes | no | no | no |
| Multi-app debugging | yes | no | no | no |
| Language-agnostic ingestion | yes | no | no | no |
| N+1 detection | yes | no | no | yes |
| MCP/AI integration | yes | no | no | no |
| Custom collectors | yes | yes | yes | yes |
| Production-safe mode | no | yes | yes | yes |
| Browser extension | no | no | no | yes |

Unique advantages: live inspection (routes, DB schema, config, git, files), multi-app service registry, language-agnostic ingestion API, MCP server for AI assistants.

## Remaining Gaps

| Gap | Impact | Vector |
|-----|--------|--------|
| No production-safe mode (sampling, minimal overhead) | Medium | Performance |
| No browser extension | Low | DX |
| SSE polls storage hash every 1s (ties up PHP worker) | High | Performance |
| No code splitting for frontend modules | Medium | Performance |
| FileStorage doesn't scale beyond ~5000 entries | Medium | Performance |
| No API versioning | Medium | Architecture |

## Development Vectors

### Performance (next priority)
- Replace polling SSE with inotify/shared memory
- Frontend code splitting + virtualization for large lists
- SQLite storage backend (indexed queries, efficient GC)
- Async storage writes
- Sampling mode (collect every Nth request)

### DX Polish (continuous)
- OpenAPI spec generation from PHP routes
- TypeScript types auto-generated from OpenAPI
- Full-text search across all debug entries
- Diff between two debug entries
- Bookmark/pin debug entries
- Export as shareable JSON/HTML report
- Mobile-responsive layout

### Observability (v2.0+)
- Memory usage tracking per request
- Request timeline waterfall view
- Anomaly detection ("this request is 3x slower than average")
- OpenTelemetry export to Jaeger/Grafana
- Performance regression alerts

### Team Platform (v3.0+)
- Multi-user access control
- Shared debugging sessions
- Cloud-hosted option
- CI/CD integration (assert no N+1 in test suite)
- Plugin marketplace
