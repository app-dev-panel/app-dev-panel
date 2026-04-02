# Toolbar Redesign — Brainstorm

## Status Quo: What Works, What Doesn't

### Current Design (Classic Bottom Bar)
- **Works**: familiar pattern (Symfony, Laravel DebugBar, Yii Debug), low cognitive load
- **Doesn't**: steals vertical space, overlaps footers/sticky elements, horizontal overflow on narrow screens, collapsed pill is easy to lose

### Fundamental Questions
1. **Should the toolbar be always visible?** — Current: yes (when expanded). Maybe not.
2. **Should it be at the bottom?** — Every PHP debugbar does this. But is it the best place?
3. **Should it show ALL metrics at once?** — Information overload vs quick scanning
4. **Should the panel be an iframe?** — Heavy, sandboxed, communication complexity

---

## The 5 Mockup Variants

| # | Name | File | Concept | Risk |
|---|------|------|---------|------|
| 1 | **Classic Bar** | `variant-1-classic-bar.html` | Refined current design | Low — safe evolution |
| 2 | **Floating Island** | `variant-2-floating-island.html` | macOS Dynamic Island pill | Medium — unfamiliar UX |
| 3 | **Side Rail** | `variant-3-side-rail.html` | IDE-style vertical panel | Medium — uses horizontal space |
| 4 | **Command Palette** | `variant-4-command-palette.html` | Spotlight/⌘K with status bar | High — hidden by default |
| 5 | **Glass HUD** | `variant-5-glassmorphism-hud.html` | Frosted glass dashboard card | Medium — heavy CSS, accessibility |

---

## Radical Ideas (Not Yet Mocked)

### A. "Toolbar as Notification Center"
Instead of a persistent bar, the toolbar only appears when something **interesting** happens:
- Slow query detected → slide in a toast with the query time
- Exception thrown → red toast with the exception class
- N+1 detected → yellow alert
- When idle → just a tiny dot or nothing at all

**Pro**: Zero visual overhead. Only shows what matters.
**Con**: You can't proactively explore metrics.

### B. "Edge Tabs" (Browser DevTools Style)
Multiple tabs on the edge of the screen (like browser tabs rotated 90°):
- Each collector gets its own tab: DB, HTTP, Logs, Events, etc.
- Click a tab → side panel opens with that collector's details inline
- No need for iframe at all — render collector UIs directly

**Pro**: Direct access to each collector. No iframe overhead.
**Con**: Needs all collector UIs ported to toolbar bundle (bundle size).

### C. "Headless Toolbar + Browser Extension"
The toolbar doesn't render in the page at all. Instead:
- ADP injects a tiny `<script>` that puts debug data into `window.__ADP__`
- A browser extension reads it and shows in its own popup/devtools panel
- Like Vue DevTools or React DevTools but for ADP

**Pro**: Zero DOM footprint. Native browser UI. Persistent across pages.
**Con**: Requires extension install. Not cross-browser without effort.

### D. "Picture-in-Picture" (Floating Window)
Use the experimental [Document Picture-in-Picture API](https://developer.chrome.com/docs/web-platform/document-picture-in-picture):
- Toolbar opens as a separate floating window on the desktop
- Stays on top of all browser windows
- Can be positioned anywhere, resized
- Works even when navigating between pages

**Pro**: Truly non-intrusive. Persistent. Familiar PiP concept.
**Con**: Chromium-only (for now). Experimental API.

### E. "Inline Annotations"
Instead of a separate toolbar, annotate the page directly:
- Show response time next to the URL bar (via extension)
- Highlight slow-rendered DOM elements with colored borders
- Show query count on DB-related UI components
- Add small badges near forms with validation info

**Pro**: Context-aware. Metrics shown where they matter.
**Con**: Very complex. Framework-specific DOM knowledge needed.

### F. "Terminal-style" (Draggable Console)
A retro terminal/console that floats on the page:
- Monospace text output
- Scrollable log
- Type commands to inspect (`db`, `logs`, `routes`, `env`)
- Auto-prints new entries as they arrive

**Pro**: Familiar to devs. Text-first. Lightweight.
**Con**: Not visual. Can't show complex data (tables, traces).

---

## Ideas for Current Toolbar Improvements

### Quick Wins
1. **Keyboard shortcuts** — ⌘D to toggle toolbar, ⌘⇧D to toggle panel
2. **Auto-collapse on navigation** — toolbar collapses when page navigates
3. **Position memory** — remember collapsed/expanded per-domain
4. **Mini mode** — single-line mode showing only 3-4 key metrics
5. **Drag to reposition** — let user place the collapsed pill anywhere

### Medium Effort
6. **Sparkline charts** — tiny inline charts in metric chips (last 10 requests)
7. **Diff mode** — compare current request with previous (highlight changes)
8. **Timeline view** — horizontal timeline showing request lifecycle phases
9. **Quick filters** — click a metric to instantly filter in the panel
10. **Metric thresholds** — user-configurable thresholds (e.g., "warn if >100ms")

### Ambitious
11. **Live streaming** — SSE-powered real-time metric updates without polling
12. **Multi-request view** — show metrics for last N requests side by side
13. **AI summary** — "This request is slow because of N+1 queries on User model"
14. **Performance budget** — set budgets per metric, toolbar goes red when exceeded
15. **Collaborative debugging** — share a debug entry link with a teammate

---

## Mobile / Responsive Considerations

The current toolbar doesn't work well on mobile. Options:
- **FAB-only mode** on mobile (already partially implemented)
- **Sheet drawer** — swipe up from bottom edge to reveal metrics
- **Corner badge** — tiny overlay badge with status color, tap to expand
- **Toast notifications** — only show exceptional events on mobile

---

## Accessibility Notes

Whatever variant we choose:
- Must support keyboard navigation (Tab, Enter, Escape)
- Must have proper ARIA labels (role="toolbar", aria-expanded, etc.)
- Must maintain sufficient contrast ratios (especially glassmorphism!)
- Focus trap when modal/palette is open
- Reduced motion support (@media prefers-reduced-motion)

---

## Recommendation

**Hybrid approach**: Combine the best elements:

1. **Default**: Variant 1 (Classic Bar) — safe, familiar, refined
2. **Add**: Command Palette overlay (Variant 4) as keyboard shortcut ⌘D
3. **Add**: Mini status bar mode (from Variant 4) as an alternative collapsed state
4. **Add**: User preference for position (bottom bar / side rail / floating island)
5. **Future**: Browser extension for headless mode (Idea C)

This gives users the familiar toolbar they expect, plus power-user features
(command palette, keyboard shortcuts) without breaking the existing UX.
