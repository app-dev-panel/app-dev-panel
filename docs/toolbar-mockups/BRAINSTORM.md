# Toolbar Redesign — Brainstorm (COMPLETED)

Status: 2026-04-04. Design exploration complete. Toolbar implemented with multiple variants.

## Implemented

The toolbar shipped with: DebugToolbar (classic bar), FloatMetrics (floating island), SideMetrics (side rail),
RequestHeroBar, drag/snap positioning, 11+ metric items.

HTML mockup files (23 variants) removed — served their purpose during design phase.

## Future Ideas (Not Yet Implemented)

These ideas from the brainstorm remain relevant for future iterations:

### Toolbar Enhancements
- **Keyboard shortcuts** — ⌘D to toggle toolbar, ⌘⇧D to toggle panel
- **Sparkline charts** — tiny inline charts in metric chips (last 10 requests)
- **Diff mode** — compare current request with previous (highlight changes)
- **Metric thresholds** — user-configurable thresholds (e.g., "warn if >100ms")
- **Performance budget** — set budgets per metric, toolbar goes red when exceeded

### Alternative Approaches (Long-Term)
- **Browser extension** — headless toolbar via `window.__ADP__` + devtools panel (zero DOM footprint)
- **AI summary** — "This request is slow because of N+1 queries on User model"
- **Live streaming** — SSE-powered real-time metric updates (see `docs/design/live-streaming.md`)

### Accessibility (Ongoing)
- Keyboard navigation (Tab, Enter, Escape)
- ARIA labels (role="toolbar", aria-expanded)
- Reduced motion support (@media prefers-reduced-motion)
