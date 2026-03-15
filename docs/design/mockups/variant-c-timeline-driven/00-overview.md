# Variant C: Timeline-Driven — Overview

## Philosophy

Timeline-Driven treats the request lifecycle as the primary navigation axis. Instead of switching
between collector tabs, all debug data is unified on a single horizontal timeline. Every collector
contributes spans to this timeline. Users click segments to drill into details.

Inspired by: Chrome Performance tab, Jaeger, Zipkin, Xdebug trace viewer.

Core insight: debugging is fundamentally about understanding **when** things happened and **how long**
they took. The timeline makes these relationships immediately visible.

## Timeline Color Scheme

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                        TIMELINE COLOR LEGEND                                                       │
├──────────────────────┬──────────┬───────────────────────────────────────────────────────────────────────────────────┤
│ Category             │ Color    │ Hex / Description                                                                │
├──────────────────────┼──────────┼───────────────────────────────────────────────────────────────────────────────────┤
│ Request lifecycle    │ Blue     │ #2196F3 — Overall request span, middleware, routing                              │
│ Database queries     │ Green    │ #4CAF50 — SQL queries, transactions, connection time                             │
│ Events / Hooks       │ Orange   │ #FF9800 — Event dispatch, listener execution                                     │
│ Errors / Exceptions  │ Red      │ #F44336 — Uncaught exceptions, error-level logs                                  │
│ Cache operations     │ Teal     │ #009688 — Cache reads, writes, misses                                            │
│ HTTP client          │ Purple   │ #9C27B0 — Outbound HTTP requests                                                 │
│ Logging              │ Grey     │ #607D8B — Log entries (info, warning, debug)                                     │
│ View rendering       │ Amber    │ #FFC107 — Template compilation, rendering                                        │
│ Custom / User        │ Indigo   │ #3F51B5 — User-defined spans via profiler API                                    │
├──────────────────────┼──────────┼───────────────────────────────────────────────────────────────────────────────────┤
│ Selected segment     │ Bright   │ White border + elevated shadow on selected bar                                   │
│ Hovered segment      │ Light    │ 20% lighter variant + tooltip                                                    │
│ Slow threshold       │ Striped  │ Diagonal hash pattern overlaid on bars exceeding threshold                       │
└──────────────────────┴──────────┴───────────────────────────────────────────────────────────────────────────────────┘
```

## Interaction Model

```
┌───────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                          INTERACTION PATTERNS                                                    │
├────────────────────┬──────────────────────────────────────────────────────────────────────────────────────────────┤
│ Action             │ Behavior                                                                                    │
├────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────┤
│ Hover on bar       │ Show tooltip: collector name, duration, start offset, key metric                            │
│ Click on bar       │ Open detail panel below timeline; highlight bar with white border                           │
│ Mousewheel         │ Zoom in/out on timeline (centered on cursor position)                                       │
│ Click + Drag       │ Pan timeline left/right                                                                     │
│ Double-click bar   │ Zoom to fit that bar's time range                                                           │
│ Shift + Click      │ Select multiple bars for comparison                                                         │
│ Right-click bar    │ Context menu: copy duration, jump to source, filter by type                                 │
│ Keyboard [+] [-]   │ Zoom in / zoom out                                                                          │
│ Keyboard [Home]    │ Reset zoom to fit entire request                                                             │
│ Keyboard [Tab]     │ Cycle through bars in chronological order                                                    │
│ Minimap drag       │ Visible at top of timeline; drag viewport rectangle to navigate                             │
└────────────────────┴──────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Layout Zones

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Context Bar  — entry metadata: method, URL, status, total time, entry ID                                       │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │  Minimap  — compressed full-timeline overview, viewport indicator                                          │
│    ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ I  │                                                                                                            │
│ C  │  Timeline Zone  — waterfall bars, time axis, zoom/pan                                                      │
│ O  │  (60% of vertical space when detail panel closed)                                                          │
│ N  │                                                                                                            │
│    ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ R  │                                                                                                            │
│ A  │  Detail Panel  — tabbed detail view for selected segment                                                   │
│ I  │  (40% of vertical space, collapsible, resizable divider)                                                   │
│ L  │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Design Tokens

```
Timeline bar height:     24px (main bars), 18px (nested children)
Timeline row gap:         4px
Time axis tick spacing:   Auto-calculated based on zoom level
Minimum bar width:        2px (very short operations still visible)
Tooltip delay:            200ms hover
Detail panel min-height:  200px
Detail panel max-height:  60% viewport
Zoom step:                20% per mousewheel tick
Transition duration:      150ms (zoom, pan, selection)
```
