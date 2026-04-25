# Ideas & Future Improvements

Status: 2026-04-04. Brainstorming — not committed to roadmap.

## 1. Collector Manifest

Auto-discovery system where each collector declares ID, required PSR interfaces, proxy class, dependencies, and frontend module path via PHP attributes or manifest file. Eliminates manual per-adapter DI wiring. Enables third-party plug-and-play collectors.

## 2. Storage Index

Maintain an `index.json` at storage root — append-only log of entry IDs with timestamps and metadata. O(1) listing instead of O(n) directory scan. Faster SSE hash computation. Rebuild from files if missing.

## 3. Frontend Improvements

- Collector auto-discovery from manifest (replace hardcoded module mapping)
- Relative time display ("2 hours ago") with absolute tooltip
- Keyboard navigation (vim-style j/k for entries)
- Diff view: compare two debug entries side-by-side
- Bookmarks: pin entries to prevent GC removal

## 4. Multi-App Dashboard

Group debug entries by service with health status, per-service counts, quick filters, and capability indicators.

## 5. Export / Share

- Export as standalone HTML report (self-contained)
- Share link with embedded data
- Export to HAR (HTTP) or Chrome DevTools trace format (timeline)

## 6. Retention Policies

Beyond simple count limit: time-based retention (last N hours/days), tag-based indefinite retention, per-collector retention rules.
