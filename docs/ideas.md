# Ideas & Future Improvements

Status as of 2026-03-21. Brainstorming — not committed to roadmap yet.

---

## 1. Collector Manifest

**Problem**: Collectors are registered via framework-specific DI configs. Each adapter must manually wire every collector.
Adding a new collector requires editing multiple adapter configs.

**Idea**: Introduce a `manifest.json` (or PHP attribute-based) system where each collector declares:
- ID, name, description
- Required PSR interfaces (e.g., `Psr\Log\LoggerInterface`)
- Required proxy class
- Default enabled/disabled state
- Dependencies on other collectors
- Frontend module path (for the panel to auto-discover UI components)

**Benefits**:
- Adapters auto-discover collectors from manifest — no manual wiring per collector
- Frontend auto-registers panels based on manifest — no hardcoded collector-to-page mapping
- Third-party collectors become plug-and-play (install package → auto-registered)
- `adp collector:list` CLI command can introspect available collectors

**Implementation options**:
- PHP attributes (`#[Collector(id: 'log', requires: [LoggerInterface::class])]`) scanned at build time
- Static `manifest.php` per collector package returning array config
- JSON manifest checked into each collector package

---

## 2. File Metadata Improvements

**Done**: Added `mtime` (modification time) to `FileController::serializeFileInfo()`.

**Next steps**:
- Add `ctime` (creation time / inode change time) — `$file->getCTime()`
- Add `atime` (last access time) — `$file->getATime()`
- Add human-readable date formatting option via query param (`?format=iso8601`)
- Show file modification time in frontend file explorer UI
- Sort files by mtime in directory listings (optional, via query param `?sort=mtime`)

---

## 3. Storage Manifest / Index

**Problem**: `FileStorage` scans directories with glob to list debug entries. With many entries this gets slow.

**Idea**: Maintain an `index.json` manifest at the storage root:
- Append-only log of debug entry IDs with timestamps, collector list, summary metadata
- Read index instead of scanning filesystem for listings
- Rebuild index from files if missing (backward compatible)
- GC updates the index when removing old entries

**Benefits**:
- O(1) listing instead of O(n) directory scan
- Faster SSE hash computation (hash index file instead of re-reading all summaries)
- Enables filtering/search without loading all summary files

---

## 4. Frontend Improvements

- **Collector auto-discovery**: Frontend reads manifest to dynamically register collector pages instead of hardcoded mapping
- **File explorer mtime column**: Show modification dates in the file browser table
- **Relative time display**: "2 hours ago" style timestamps with tooltip for absolute time
- **Keyboard navigation**: Vim-style keybindings for navigating debug entries (j/k, enter to view)
- **Diff view**: Compare two debug entries side-by-side (e.g., before/after a code change)
- **Bookmarks**: Pin/star debug entries to prevent GC from removing them

---

## 5. Multi-App Dashboard

**Problem**: With service registry, multiple apps can send debug data. But the UI treats them as a flat list.

**Idea**: Dashboard view grouping debug entries by service, with:
- Service health status (online/offline from heartbeat)
- Per-service entry count and last activity
- Quick filters by service name
- Service-specific collector capabilities shown

---

## 6. Export / Share

- Export debug entry as standalone HTML report (self-contained, no server needed)
- Share link with embedded data (base64 in URL hash, or short-lived hosted link)
- Export to common formats: HAR (for HTTP), Chrome DevTools trace format (for timeline)

---

## 7. Retention Policies

**Problem**: GC uses a simple count limit (default 50). No time-based retention.

**Idea**: Support retention rules:
- Keep last N entries (current behavior)
- Keep entries from last N hours/days
- Keep entries matching a tag/label indefinitely
- Per-collector retention (keep more log entries, fewer request entries)
